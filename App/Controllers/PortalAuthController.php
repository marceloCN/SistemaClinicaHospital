<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CuentaPersona;
use App\Models\Persona;
use App\Models\RecuperacionPassword;
use App\Models\Rol;
use App\Requests\LoginRequest;
use App\Controllers\Controller;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\View;
use Lib\Support\Csrf;
use Lib\Support\Hash;
use Lib\Support\Mail;
use Lib\Support\Session;
use App\Requests\StorePacienteRegistroRequest;
use Lib\Core\Database;

class PortalAuthController extends Controller
{
    private const INTENTOS_MAXIMOS = 3;
    private const SESION_PREFIJO = 'portal_';

    public function index(Request $request): Response
    {
        if (Session::get(self::SESION_PREFIJO . 'loged', false)) {
            return $this->redirect('portal_dashboard');
        }

        return $this->view('portal.login');
    }

    public function verificar(LoginRequest $request): Response
    {
        $data = $request->validated();
        $usuario = trim((string) $data['usuario']);
        $password = (string) $data['password'];

        $cuenta = CuentaPersona::query()->where('usuario', $usuario)->orWhere('correo', $usuario)->first();

        if ($cuenta === null) {
            // no es necesario registrarlo porque no existe el usuario
            //$this->portalRegistrarAcceso(null, null, $usuario, 'FALLIDO', 'Usuario no encontrado');
            return $this->json(['success' => false, 'message' => 'Credenciales incorrectas.']);
        }

        $persona = Persona::query()->find($cuenta['id_persona']);

        if ($persona === null || !$persona['estado']) {
            return $this->json(['success' => false, 'message' => 'Usuario inactivo o no encontrado.']);
        }

        if ($this->estaBloqueada($cuenta)) {
            return $this->json(['success' => false,'message' => 'Cuenta bloqueada por intentos fallidos. Intente más tarde.',]);
        }

        //aqui verifica si la contraseña no es la correcta
        if (!$this->passwordMatches($password, $cuenta['password_hash'])) {
            $intentos = $this->portalRegistrarAcceso((int) $cuenta['id'], (int) $persona['id'],$usuario,'FALLIDO','Intento fallido');

            $mensaje = 'Credenciales incorrectas.';
            if ($intentos >= self::INTENTOS_MAXIMOS) {
                $mensaje = 'Cuenta bloqueada por 30 minutos por superar los intentos permitidos.';
            } else {
                $mensaje .= ' Te quedan ' . (self::INTENTOS_MAXIMOS - $intentos) . ' intento(s).';
            }

            return $this->json(['success' => false, 'message' => $mensaje], 401);
        }

        $this->registrarBitacoraAcceso((int) $cuenta['id'], $usuario, 'EXITOSO', 'Inicio de sesión en portal');
        //$this->portalRegistrarAcceso((int) $cuenta['id'], (int) $persona['id'], $usuario, 'EXITOSO', 'Inicio de sesión en portal');

        $rol = Rol::query()->find($persona['id_rol']);
        $permisos = $this->cargarPermisos((int) $persona['id_rol']);//me obtiene un array de codigos

        Session::regenerate(true);

        Session::set(self::SESION_PREFIJO . 'loged', true);
        Session::set(self::SESION_PREFIJO . 'id_persona', (int) $persona['id']);
        Session::set(self::SESION_PREFIJO . 'id_rol', (int) $persona['id_rol']);
        Session::set(self::SESION_PREFIJO . 'rol_clave', (string) ($rol['clave'] ?? ''));
        Session::set(self::SESION_PREFIJO . 'nombre', trim($persona['nombre'] . ' ' . $persona['apellido']));
        Session::set(self::SESION_PREFIJO . 'email', $cuenta['correo']);
        Session::set(self::SESION_PREFIJO . 'usuario', $cuenta['usuario']);
        Session::set(self::SESION_PREFIJO . 'permisos', $permisos);

        Csrf::regenerate();

        return $this->json([
            'success' => true,
            'redirect' => route('portal_dashboard') ?? '/portal/dashboard',
        ]);
    }

    public function logout(Request $request): Response
    {
        if (!$request->isPost()) {
            return Response::make('Método no permitido.', 405);
        }

        if (!Csrf::validate((string) $request->input('_token'))) {
            return Response::make('Token CSRF inválido.', 403);
        }

        $idPersona = $this->idPersona();
        //$idPersona = (int) Session::get(self::SESION_PREFIJO . 'id_persona', 0);
        $usuario = Session::get(self::SESION_PREFIJO . 'usuario');
        if ($idPersona > 0) {
            $this->registrarBitacoraAcceso((int) $idPersona, $usuario, 'CERRADO', 'Cierre de sesión en portal');
        }

        Session::forget(self::SESION_PREFIJO . 'loged');
        Session::forget(self::SESION_PREFIJO . 'id_persona');
        Session::forget(self::SESION_PREFIJO . 'id_rol');
        Session::forget(self::SESION_PREFIJO . 'rol_clave');
        Session::forget(self::SESION_PREFIJO . 'nombre');
        Session::forget(self::SESION_PREFIJO . 'email');
        Session::forget(self::SESION_PREFIJO . 'usuario');
        Session::forget(self::SESION_PREFIJO . 'permisos');

        return $this->redirect('portal_login');
    }

    public function dashboard(Request $request): Response
    {
        //$rolClave = (string) Session::get(self::SESION_PREFIJO . 'rol_clave', '');
        $rolClave = $this->rolClave();
        $nombre = (string) Session::get(self::SESION_PREFIJO . 'nombre', 'Usuario');

        return $this->view('portal.base.layout', [
            'rolClave' => $rolClave,
            'nombre' => $nombre,
            'active' => 'dashboard',
            'content' => View::render('portal.dashboard', [
                'rolClave' => $rolClave,
                'nombre' => $nombre,
            ]),
        ]);
    }

    /**
     * Comprueba en vivo que el usuario aún no esté en uso (registro público).
     */
    public function usuarioDisponible(Request $request): Response
    {
        $usuario = mb_strtolower(trim((string) $request->input('usuario', '')));

        if ($usuario === '') {
            return $this->json(['success' => false, 'disponible' => false, 'message' => 'Ingresa un usuario.'], 422);
        }

        if (mb_strlen($usuario) < 3) {
            return $this->json([
                'success' => false,
                'disponible' => false,
                'message' => 'El usuario debe tener al menos 3 caracteres.',
            ]);
        }

        $existe = CuentaPersona::query()->where('usuario', $usuario)->exists();

        return $this->json([
            'success' => true,
            'disponible' => !$existe,
            'message' => $existe ? 'Ese nombre de usuario ya está en uso.' : 'Usuario disponible.',
        ]);
    }

    /**
     * Comprueba en vivo que el correo aún no esté registrado (registro público).
     */
    public function correoDisponible(Request $request): Response
    {
        $correo = mb_strtolower(trim((string) $request->input('correo', '')));

        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['success' => false, 'disponible' => false, 'message' => 'Ingresa un correo válido.'], 422);
        }

        $existe = CuentaPersona::query()->where('correo', $correo)->exists();

        return $this->json([
            'success' => true,
            'disponible' => !$existe,
            'message' => $existe ? 'Ese correo ya está registrado.' : 'Correo disponible.',
        ]);
    }

    public function registrar(StorePacienteRegistroRequest $request): Response
    {
        $data = $request->validated();

        $ci = mb_strtoupper(trim((string) $data['ci']));
        $nombre = trim((string) $data['nombre']);
        $apellido = trim((string) $data['apellido']);
        $telefono = trim((string) ($data['telefono'] ?? ''));
        $direccion = trim((string) ($data['direccion'] ?? ''));
        $sexo = trim((string) ($data['sexo'] ?? ''));
        $usuario = mb_strtolower(trim((string) $data['usuario']));
        $correo = mb_strtolower(trim((string) $data['correo']));
        $password = (string) $data['password'];
        $fechaNacimiento = trim((string) $request->input('fecha_nacimiento', ''));

        if ($error = $this->validarDatosRegistro($ci, $usuario, $correo, $password, $fechaNacimiento)) {
            return $error;
        }

        $rol = Rol::query()->where('clave', 'PAC')->first();

        if ($rol === null) {
            error_log('No se encontró el rol PAC para el registro de paciente.');

            return $this->json([
                'success' => false,
                'message' => 'No se pudo registrar el usuario: el rol de paciente no está disponible.',
            ], 500);
        }

        try {
            $idPersona = Database::transaction(function () use ($ci,$nombre,$apellido,$telefono,$direccion,$correo,$fechaNacimiento,$sexo,$usuario,$data, $rol
            ): int {
                Persona::query()->insert(['ci' => $ci, 'nombre' => $nombre,'apellido' => $apellido,
                    'telefono' => $telefono !== '' ? $telefono : null,'direccion' => $direccion !== '' ? $direccion : null,
                    'correo' => $correo, 'fecha_nacimiento' => $fechaNacimiento !== '' ? $fechaNacimiento : null,
                    'sexo' => $sexo !== '' ? $sexo : null,'estado' => true,'id_rol' => (int) $rol['id'],
                ]);

                $persona = Persona::query()->where('ci', $ci)->first();

                if ($persona === null) {
                    throw new \RuntimeException('No se pudo recuperar la persona creada.');
                }

                $idPersona = (int) $persona['id'];

                CuentaPersona::query()->insert([
                    'id_persona' => $idPersona,
                    'usuario' => $usuario,
                    'correo' => $correo,
                    'password_hash' => Hash::make((string) $data['password']),
                    'intentos_fallidos' => 0,
                    'bloqueado' => false,
                ]);

                return $idPersona;
            });

            $this->registrarBitacoraAcceso($idPersona, $usuario,'EXITOSO','Registro de cuenta de usuario paciente en el portal');

             $this->registrarBitacoraAccion($idPersona,'PERFIL','INSERT',$idPersona, [],
            [
                'ci' => $ci, 'nombre' => $nombre,'apellido' => $apellido,
                'telefono' => $telefono !== '' ? $telefono : null,'direccion' => $direccion !== '' ? $direccion : null,
                'correo' => $correo, 'fecha_nacimiento' => $fechaNacimiento !== '' ? $fechaNacimiento : null,
                'sexo' => $sexo !== '' ? $sexo : null,'estado' => true,'id_rol' => (int) $rol['id'],

            ],'Insercion de un nuevo paciente');

            $this->registrarBitacoraAccion($idPersona,'CUENTA_PERSONA','INSERT',$idPersona, [],
            [
                'id_persona' => $idPersona,'usuario' => $usuario,
                'correo' => $correo,'password_hash' => Hash::make((string) $data['password']),
                'intentos_fallidos' => 0,'bloqueado' => false,

            ],'Insercion de un nuevo paciente en la cuenta de usuario');


        } catch (\Throwable $e) {
            error_log('Error al registrar usuario paciente: ' . $e->getMessage());

            $mensaje = 'No se pudo registrar el usuario. Revisa los datos e intenta de nuevo.';

            if (config('app.debug')) {
                $mensaje .= ' [debug] ' . $e->getMessage();
            }

            return $this->json([
                'success' => false,
                'message' => $mensaje,
            ], 500);
        }

        $mensaje = 'El usuario "' . $usuario . '" ya ha sido registrado. '
            . 'Ya puedes ingresar al portal de pacientes con tus datos.';

        Session::flash('mensaje_registro', $mensaje);

        return $this->json([
            'success' => true,
            'message' => $mensaje,
            'redirect' => route('inicio') ?? '/',
        ]);
    }

    /* =====================================================================
       RECUPERACIÓN DE CONTRASEÑA (PORTAL)
       ===================================================================== */

    public function mostrarFormularioRecuperacion(Request $request): Response
    {
        if (Session::get(self::SESION_PREFIJO . 'loged', false)) {
            return $this->redirect('portal_dashboard');
        }

        return $this->view('portal.auth.recuperar');
    }

    public function enviarRecuperacion(Request $request): Response
    {
        if (Session::get(self::SESION_PREFIJO . 'loged', false)) {
            return $this->json(['success' => false, 'message' => 'Sesión activa.'], 403);
        }

        $correo = trim((string) $request->input('correo'));

        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['success' => false, 'message' => 'Ingresa un correo electrónico válido.'], 422);
        }

        $cuenta = CuentaPersona::query()->where('correo', $correo)->first();

        if ($cuenta === null) {
            return $this->json([
                'success' => true,
                'message' => 'Si el correo está registrado, recibirás instrucciones para restablecer tu contraseña.',
            ]);
        }

        RecuperacionPassword::query()->update(
            ['estado' => 'EXPIRADO'],
            ['id_cuenta_persona' => $cuenta['id'], 'estado' => 'ACTIVO']
        );

        $token = bin2hex(random_bytes(32));

        RecuperacionPassword::query()->insert([
            'id_cuenta_persona' => $cuenta['id'],
            'token' => $token,
            'estado' => 'ACTIVO',
            'fecha_expiracion' => date('Y-m-d H:i:s', strtotime('+2 hours')),
        ]);

        $enlace = rtrim(config('app.url'), '/') . '/portal/password/restablecer/' . $token;

        try {
            Mail::to($correo)
                ->subject('Recuperación de contraseña - ' . config('app.name'))
                ->send($this->buildEmailRecuperacion($enlace));
        } catch (\Throwable $e) {
            error_log('Error enviando correo de recuperación: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'No se pudo enviar el correo. Intenta más tarde o contacta a soporte.',
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Si el correo está registrado, recibirás instrucciones para restablecer tu contraseña.',
        ]);
    }

    public function mostrarFormularioRestablecer(Request $request, string $token): Response
    {
        if (Session::get(self::SESION_PREFIJO . 'loged', false)) {
            return $this->redirect('portal_dashboard');
        }

        $recuperacion = $this->buscarTokenValido($token);

        if ($recuperacion === null) {
            echo "Tu token expiro, vuelve a solicitar olvide contraseña";
            //return $this->view('portal.auth.token_invalido');
        }

        return $this->view('portal.auth.restablecer', ['token' => $token]);
    }

    public function actualizarPassword(Request $request): Response
    {
        if (Session::get(self::SESION_PREFIJO . 'loged', false)) {
            return $this->json(['success' => false, 'message' => 'Sesión activa.'], 403);
        }

        $token = trim((string) $request->input('token'));
        $password = (string) $request->input('password');

        if ($token === '') {
            return $this->json(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        if (strlen($password) < 6) {
            return $this->json(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.']);
        }

        $recuperacion = $this->buscarTokenValido($token);

        if ($recuperacion === null) {
            return $this->json(['success' => false, 'message' => 'El enlace expiró o ya fue usado.'], 410);
        }

        CuentaPersona::query()->update(
            ['password_hash' => Hash::make($password)],
            ['id' => $recuperacion['id_cuenta_persona']]
        );

        RecuperacionPassword::query()->update(
            ['estado' => 'USADO', 'fecha_uso' => date('Y-m-d H:i:s')],
            ['id' => $recuperacion['id']]
        );

        return $this->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente.',
            'redirect' => route('portal_login') ?? '/portal/login',
        ]);
    }

    private function buscarTokenValido(string $token): ?array
    {
        $recuperacion = RecuperacionPassword::query()
            ->where('token', $token)
            ->where('estado', 'ACTIVO')
            ->first();

        if ($recuperacion === null) {
            return null;
        }

        if (strtotime((string) $recuperacion['fecha_expiracion']) < time()) {
            RecuperacionPassword::query()->update(
                ['estado' => 'EXPIRADO'],
                ['id' => $recuperacion['id']]
            );
            return null;
        }

        return $recuperacion;
    }

    private function buildEmailRecuperacion(string $enlace): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperación de contraseña</title>
</head>
<body style="font-family: Inter, Arial, sans-serif; line-height: 1.6; color: #0b2a38; background: #f7f9f6; padding: 24px;">
    <div style="max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 18px; padding: 36px; box-shadow: 0 8px 24px rgba(11,42,56,0.08);">
        <h2 style="color: #0f8b84; margin-top: 0;">Recuperación de contraseña</h2>
        <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en el portal de la clínica.</p>
        <p>Haz clic en el siguiente botón para crear una nueva contraseña. El enlace es válido por 2 horas.</p>
        <p style="text-align: center; margin: 32px 0;">
            <a href="{$enlace}" style="display: inline-block; background: #0f8b84; color: #ffffff; padding: 14px 28px; border-radius: 50px; text-decoration: none; font-weight: 600;">Restablecer contraseña</a>
        </p>
        <p style="font-size: 0.9rem; color: #5d7078;">Si no solicitaste este cambio, ignora este correo. Tu contraseña actual seguirá siendo válida.</p>
        <hr style="border: none; border-top: 1px solid #dfe8e5; margin: 24px 0;">
        <p style="font-size: 0.85rem; color: #5d7078;">Si el botón no funciona, copia y pega este enlace en tu navegador:<br><a href="{$enlace}" style="color: #0f8b84;">{$enlace}</a></p>
    </div>
</body>
</html>
HTML;
    }

    /* =====================================================================
       AUXILIARES
       ===================================================================== */

    /**
     * Verifica fecha de nacimiento, reglas de contraseña y unicidad de
     * cédula/usuario/correo. Devuelve la respuesta JSON de rechazo o
     * null si todos los datos son válidos.
     */
    private function validarDatosRegistro(
        string $ci,
        string $usuario,
        string $correo,
        string $password,
        string $fechaNacimiento
    ): ?Response {
        if ($fechaNacimiento !== '' && !$this->fechaValida($fechaNacimiento)) {
            return $this->json([
                'success' => false,
                'message' => 'La fecha de nacimiento no es válida.',
                'errors' => ['fecha_nacimiento' => ['La fecha de nacimiento no es válida.']],
            ]);
        }

        $pendientes = $this->passwordReglasPendientes($this->passwordReglas($password));

        if ($pendientes !== []) {
            return $this->json([
                'success' => false,
                'message' => 'La contraseña no cumple: ' . implode(', ', $pendientes) . '.',
                'errors' => ['password' => ['La contraseña no cumple los requisitos de seguridad.']],
            ]);
        }

        $verificaciones = [
            ['ci', Persona::query()->where('ci', $ci)->exists(), 'Ya existe una persona registrada con esa cédula de identidad.'],
            ['usuario', CuentaPersona::query()->where('usuario', $usuario)->exists(), 'Ese nombre de usuario ya está en uso.'],
            ['correo', CuentaPersona::query()->where('correo', $correo)->exists(), 'Ese correo ya está registrado.'],
        ];

        foreach ($verificaciones as [$campo, $existe, $mensaje]) {
            if ($existe) {
                return $this->json([
                    'success' => false,
                    'message' => $mensaje,
                    'errors' => [$campo => [$mensaje]],
                ]);
            }
        }

        return null;
    }

    /** Evalúa las reglas de seguridad de la contraseña. */
    private function passwordReglas(string $password): array
    {
        return [
            '8 caracteres o más' => mb_strlen($password) >= 8,
            'una mayúscula' => preg_match('/[A-ZÁÉÍÓÚÑ]/u', $password) === 1,
            'una minúscula' => preg_match('/[a-záéíóúñ]/u', $password) === 1,
            'un número' => preg_match('/\d/', $password) === 1,
            'un símbolo (!@#$...)' => preg_match('/[^A-Za-z0-9]/', $password) === 1,
        ];
    }

    /** Devuelve las reglas de contraseña que aún no se cumplen. */
    private function passwordReglasPendientes(array $reglas): array
    {
        $pendientes = [];

        foreach ($reglas as $regla => $ok) {
            if (!$ok) {
                $pendientes[] = $regla;
            }
        }

        return $pendientes;
    }

    private function estaBloqueada(array $cuenta): bool
    {
        if ((bool) $cuenta['bloqueado']) {
            $hasta = $cuenta['bloqueado_hasta'];
            if ($hasta === null) {
                return true;
            }
            return strtotime((string) $hasta) > time();
        }

        return false;
    }

    private function passwordMatches(string $plain, ?string $stored): bool
    {
        if ($stored === null || $stored === '') {
            return false;
        }

        if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2')) {
            return Hash::check($plain, $stored);
        }

        return hash_equals($stored, $plain);
    }
}
