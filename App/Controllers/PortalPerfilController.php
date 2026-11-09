<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CuentaPersona;
use App\Models\Paciente;
use App\Models\Permiso;
use App\Models\Persona;
use App\Models\Rol;
use App\Controllers\Controller;
use App\Requests\UpdateAccesoPerfilRequest;
use App\Requests\UpdatePerfilRequest;
use Lib\Core\Database;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\View;
use Lib\Support\Hash;
use Lib\Support\Session;

/**
 * Perfil del usuario del portal.
 *
 * Permite consultar y actualizar los datos de la persona (nombre, apellido,
 * teléfono y dirección), los datos de acceso (usuario y correo) y la
 * contraseña. El correo y el nombre de usuario deben ser únicos: se
 * verifican antes de guardar y se excluye la cuenta del propio usuario.
 *
 * El guardado está separado en dos operaciones independientes:
 *  - guardarAcceso(): datos de acceso (usuario y correo).
 *  - guardar(): datos personales y, opcionalmente, la contraseña.
 */
class PortalPerfilController extends Controller
{
    private const SESION_PREFIJO = 'portal_';

    /**
     * Muestra la página de perfil con los datos actuales del usuario.
     */
    public function index(Request $request): Response
    {
        $idPersona = $this->idPersona();

        $persona = Persona::query()->find($idPersona);
        $cuenta = CuentaPersona::query()->where('id_persona', $idPersona)->first();

        if ($persona === null || $cuenta === null) {
            return $this->redirect('portal_dashboard');
        }

        $rol = Rol::query()->find($persona['id_rol']);
        $paciente = Paciente::query()->find($idPersona);

        $iniciales = mb_strtoupper(mb_substr((string) $persona['nombre'], 0, 1)
            . mb_substr((string) $persona['apellido'], 0, 1));

        return $this->view('portal.base.layout', [
            'rolClave' => $this->rolClave(),
            'nombre' => $this->nombre(),
            'active' => 'perfil',
            'content' => View::render('portal.perfil.index', [
                'persona' => $persona,
                'cuenta' => $cuenta,
                'rol' => $rol,
                'paciente' => $paciente,
                'iniciales' => $iniciales,
                'rolClave' => $this->rolClave(),
                'nombre' => $this->nombre(),
            ]),
        ]);
    }

    /**
     * Guarda los datos de acceso: nombre de usuario y correo.
     */
    public function guardarAcceso(UpdateAccesoPerfilRequest $request): Response
    {
        $idPersona = $this->idPersona();

        $persona = Persona::query()->find($idPersona);
        $cuenta = CuentaPersona::query()->where('id_persona', $idPersona)->first();

        if ($persona === null || $cuenta === null) {
            return $this->json([
                'success' => false,
                'message' => 'No se encontró tu cuenta en el portal.',
            ], 404);
        }

        $data = $request->validated();

        $usuario = mb_strtolower(trim((string) $data['usuario']));
        $correo = mb_strtolower(trim((string) $data['correo']));

        // El usuario y el correo no pueden pertenecer a otra cuenta.
        $errorUsuario = $this->usuarioEnUso($usuario, $idPersona);

        if ($errorUsuario !== null) {
            return $this->json([
                'success' => false,
                'message' => $errorUsuario,
                'errors' => ['usuario' => [$errorUsuario]],
            ], 422);
        }

        $errorCorreo = $this->correoEnUso($correo, $idPersona);

        if ($errorCorreo !== null) {
            return $this->json([
                'success' => false,
                'message' => $errorCorreo,
                'errors' => ['correo' => [$errorCorreo]],
            ], 422);
        }

        $datosCuenta = [
            'usuario' => $usuario,
            'correo' => $correo,
        ];

        try {
            Database::transaction(function () use ($idPersona, $cuenta, $correo, $datosCuenta): void {
                // El correo también se conserva en persona para la ficha del usuario.
                Persona::query()->update(['correo' => $correo], ['id' => $idPersona]);
                CuentaPersona::query()->update($datosCuenta, ['id' => (int) $cuenta['id']]);
            });
        } catch (\Throwable $e) {
            error_log('Error al actualizar los datos de acceso del portal: ' . $e->getMessage());

            if ($this->esViolacionUnicidad($e)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Ese nombre de usuario o correo ya está registrado.',
                ], 422);
            }

            return $this->json([
                'success' => false,
                'message' => 'No se pudieron guardar los datos de acceso. Revisa los datos e intenta de nuevo.',
            ], 500);
        }

        Session::set(self::SESION_PREFIJO . 'email', $correo);
        Session::set(self::SESION_PREFIJO . 'usuario', $usuario);

        $this->registrarBitacoraAccion($idPersona, 'PERFIL', 'ACTUALIZAR_ACCESO',$idPersona,
            [
                'correo' => (string) $persona['correo'],
                'usuario' => (string) $cuenta['usuario'],
            ],
            [
                'correo' => $correo,
                'usuario' => $usuario,
            ],
            'Actualización de datos de acceso en el portal'
        );

        return $this->json([
            'success' => true,
            'message' => 'Tus datos de acceso se actualizaron correctamente.',
            'usuario' => $usuario,
            'correo' => $correo,
        ]);
    }

    /**
     * Guarda los datos personales y, si se envía, la nueva contraseña.
     */
    public function guardar(UpdatePerfilRequest $request): Response
    {
        $idPersona = $this->idPersona();

        $persona = Persona::query()->find($idPersona);
        $cuenta = CuentaPersona::query()->where('id_persona', $idPersona)->first();

        if ($persona === null || $cuenta === null) {
            return $this->json([
                'success' => false,
                'message' => 'No se encontró tu cuenta en el portal.',
            ], 404);
        }

        $data = $request->validated();

        $nombre = trim((string) $data['nombre']);
        $apellido = trim((string) $data['apellido']);
        $telefono = trim((string) ($data['telefono'] ?? ''));
        $direccion = trim((string) ($data['direccion'] ?? ''));
        $correo = mb_strtolower(trim((string) ($data['correo'] ?? '')));

        // El correo personal no puede pertenecer a otra cuenta o persona.
        $errorCorreo = $this->correoEnUso($correo, $idPersona);

        if ($errorCorreo !== null) {
            return $this->json([
                'success' => false,
                'message' => $errorCorreo,
                'errors' => ['correo' => [$errorCorreo]],
            ],);
        }

        $nuevaPassword = (string) ($data['password'] ?? '');
        $passwordActual = trim((string) $request->input('password_actual', ''));
        $confirmacion = (string) $request->input('password_confirmation', '');

        // Contraseña: solo se cambia si se envía una nueva.
        $cambiaPassword = $nuevaPassword !== '';

        if ($cambiaPassword) {
            $errorPassword = $this->validarPasswordSegura($nuevaPassword);

            if ($errorPassword !== null) {
                return $this->json([
                    'success' => false,
                    'message' => $errorPassword,
                    'errors' => ['password' => [$errorPassword]],
                ]);
            }

            if ($nuevaPassword !== $confirmacion) {
                return $this->json([
                    'success' => false,
                    'message' => 'La confirmación de la contraseña no coincide.',
                    'errors' => ['password_confirmation' => ['La confirmación de la contraseña no coincide.']],
                ]);
            }
        }

        if (!$cambiaPassword && ($passwordActual !== '' || $confirmacion !== '')) {
            return $this->json([
                'success' => false,
                'message' => 'Completa la nueva contraseña y su confirmación, o deja esos campos vacíos.',
                'errors' => [
                    'password' => ['Completa la nueva contraseña y su confirmación, o deja esos campos vacíos.'],
                ],
            ]);
        }

        if ($cambiaPassword && !$this->passwordMatches($passwordActual, (string) $cuenta['password_hash'])) {
            return $this->json([
                'success' => false,
                'message' => 'La contraseña actual no es correcta.',
                'errors' => ['password_actual' => ['La contraseña actual no es correcta.']],
            ], 422);
        }

        $datosCuenta = [];
        $correoAnterior = mb_strtolower((string) ($cuenta['correo'] ?? ''));

        if ($correo !== '' && $correo !== $correoAnterior) {
            $datosCuenta['correo'] = $correo;
        }

        if ($cambiaPassword) {
            $datosCuenta['password_hash'] = Hash::make($nuevaPassword);
            $datosCuenta['intentos_fallidos'] = 0;
            $datosCuenta['bloqueado'] = false;
            $datosCuenta['bloqueado_hasta'] = null;
        }

        try {
            Database::transaction(function () use ($idPersona, $cuenta, $nombre, $apellido, $telefono, $direccion, $correo, $datosCuenta): void {
                Persona::query()->update([
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'telefono' => $telefono !== '' ? $telefono : null,
                    'direccion' => $direccion !== '' ? $direccion : null,
                    'correo' => $correo !== '' ? $correo : null,
                ], ['id' => $idPersona]);

                if ($datosCuenta !== []) {
                    CuentaPersona::query()->update($datosCuenta, ['id' => (int) $cuenta['id']]);
                }
            });
        } catch (\Throwable $e) {
            error_log('Error al actualizar el perfil del portal: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => 'No se pudieron guardar los cambios. Revisa los datos e intenta de nuevo.',
            ], 500);
        }

        $nombreCompleto = trim($nombre . ' ' . $apellido);

        Session::set(self::SESION_PREFIJO . 'nombre', $nombreCompleto);

        if ($correo !== '' && $correo !== $correoAnterior) {
            Session::set(self::SESION_PREFIJO . 'email', $correo);
        }

        $this->registrarBitacoraAccion($idPersona,'PERFIL','ACTUALIZAR',$idPersona,
            [
                'nombre' => (string) $persona['nombre'],
                'apellido' => (string) $persona['apellido'],
                'telefono' => $persona['telefono'],
                'direccion' => $persona['direccion'],
                'correo' => $persona['correo'],
            ],
            [
                'nombre' => $nombre,
                'apellido' => $apellido,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'correo' => $correo,
                'cambio_password' => $cambiaPassword,
            ],
            'Actualización de datos personales del perfil en el portal'
        );

        return $this->json([
            'success' => true,
            'message' => 'Tus datos se actualizaron correctamente.',
            'nombre' => $nombreCompleto,
        ]);
    }

    /**
     * Consulta AJAX: indica si un nombre de usuario está disponible.
     */
    public function usuarioDisponible(Request $request): Response
    {
        $usuario = mb_strtolower(trim((string) $request->input('usuario', '')));

        if (mb_strlen($usuario) < 3) {
            return $this->json([
                'disponible' => false,
                'message' => 'El nombre de usuario debe tener al menos 3 caracteres.',
            ]);
        }

        $error = $this->usuarioEnUso($usuario, $this->idPersona());

        if ($error !== null) {
            return $this->json([
                'disponible' => false,
                'message' => $error,
            ]);
        }

        return $this->json([
            'disponible' => true,
            'message' => 'Nombre de usuario disponible.',
        ]);
    }

    /**
     * Comprueba si el nombre de usuario ya pertenece a otra cuenta.
     */
    private function usuarioEnUso(string $usuario, int $idPersona): ?string
    {
        $cuenta = CuentaPersona::query()
            ->whereRaw('LOWER(usuario) = :usuario_buscado', ['usuario_buscado' => $usuario])
            ->first();

        if ($cuenta === null || (int) $cuenta['id_persona'] === $idPersona) {
            return null;
        }

        return 'Ese nombre de usuario ya está en uso.';
    }

    /**
     * Comprueba si el correo ya está registrado en otra cuenta o persona.
     */
    private function correoEnUso(string $correo, int $idPersona): ?string
    {
        $cuenta = CuentaPersona::query()
            ->whereRaw('LOWER(correo) = :correo_buscado', ['correo_buscado' => $correo])
            ->first();

        if ($cuenta !== null && (int) $cuenta['id_persona'] !== $idPersona) {
            return 'Ese correo ya está registrado.';
        }

        $persona = Persona::query()
            ->whereRaw('LOWER(correo) = :correo_buscado', ['correo_buscado' => $correo])
            ->first();

        if ($persona !== null && (int) $persona['id'] !== $idPersona) {
            return 'Ese correo ya está registrado.';
        }

        return null;
    }

    /**
     * Valida que la contraseña cumpla con los requisitos mínimos de seguridad.
     */
    private function validarPasswordSegura(string $password): ?string
    {
        if (mb_strlen($password) < 8) {
            return 'La contraseña debe tener al menos 8 caracteres.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return 'La contraseña debe incluir al menos una letra mayúscula.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            return 'La contraseña debe incluir al menos una letra minúscula.';
        }

        if (!preg_match('/[0-9]/', $password)) {
            return 'La contraseña debe incluir al menos un número.';
        }

        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:"\\|,.<>\/?]/', $password)) {
            return 'La contraseña debe incluir al menos un símbolo.';
        }

        return null;
    }

    /**
     * Verifica una contraseña en texto plano contra el hash guardado.
     */
    private function passwordMatches(string $plain, ?string $stored): bool
    {
        if ($plain === '' || $stored === null || $stored === '') {
            return false;
        }

        if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon2')) {
            return Hash::check($plain, $stored);
        }

        return hash_equals($stored, $plain);
    }

    /**
     * Detecta errores de unicidad lanzados por la base de datos.
     */
    private function esViolacionUnicidad(\Throwable $e): bool
    {
        if (!$e instanceof \PDOException) {
            return false;
        }

        $sqlState = (string) ($e->getCode() ?? '');

        return $sqlState === '23505' || $sqlState === '23000';
    }

    private function nombre(): string
    {
        return (string) Session::get(self::SESION_PREFIJO . 'nombre', 'Usuario');
    }
}
