<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Paciente;
use App\Models\Persona;
use App\Models\CuentaPersona;
use App\Controllers\Controller;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\View;
use Lib\Support\Csrf;
use Lib\Support\Csv;
use Lib\Support\Hash;

/**
 * Dashboard de pacientes (módulo administrativo).
 *
 * Muestra el resumen gráfico (registrados, activos, inactivos y regulares)
 * y el listado paginado de personas con rol PAC con sus cuentas de acceso,
 * filtros, registro en 2 pasos, edición de datos personales y contactos
 * de emergencia.
 */
class PortalPacienteController extends Controller
{
    private const POR_PAGINA = 10;

    /**
     * Pantalla del dashboard de pacientes.
     */
    public function index(Request $request): Response
    {
        $filtros = $this->filtros($request);
        $pagina = max(1, (int) $request->get('page', 1));

        $tab = (string) $request->get('tab', 'resumen');

        if (!in_array($tab, ['resumen', 'datos'], true)) {
            $tab = 'resumen';
        }

        $listado = Paciente::listar($filtros, $pagina, self::POR_PAGINA);
        $estadisticas = Paciente::estadisticas();
        $recientes = Paciente::recientes(10);

        return $this->view('portal.base.layout', [
            'rolClave' => $this->rolClave(),
            'nombre' => $this->nombre(),
            'active' => 'pacientes',
            'content' => View::render('portal.pacientes.index', [
                'pacientes' => $listado['items'],
                'total' => $listado['total'],
                'pagina' => $listado['pagina'],
                'paginas' => $listado['paginas'],
                'porPagina' => $listado['porPagina'],
                'numerosPagina' => $this->numerosPagina($listado['pagina'], $listado['paginas']),
                'estadisticas' => $estadisticas,
                'recientes' => $recientes,
                'tab' => $tab,
                'filtros' => $filtros,
                'filtroNombre' => (string) ($filtros['nombre'] ?? ''),
                'filtroCi' => (string) ($filtros['ci'] ?? ''),
                'filtroSexo' => (string) ($filtros['sexo'] ?? ''),
                'filtroEstado' => (string) ($filtros['estado'] ?? ''),
                'filtroRegular' => (string) ($filtros['regular'] ?? ''),
                'filtroDesde' => (string) ($filtros['desde'] ?? ''),
                'filtroHasta' => (string) ($filtros['hasta'] ?? ''),
            ]),
        ]);
    }

    /**
     * Detalle de un paciente (se carga por AJAX en el modal):
     * datos personales, cuenta de acceso y contactos de emergencia.
     */
    public function detalle(Request $request, int $id): Response
    {
        $paciente = Paciente::detalle($id);

        if ($paciente === null) {
            return $this->json([
                'success' => false,
                'message' => 'El paciente no existe.',
            ], 404);
        }

        return $this->json([
            'success' => true,
            'paciente' => [
                'id_persona' => $paciente['id_persona'],
                'nombre_completo' => $paciente['nombre_completo'],
                'nombre' => $paciente['nombre'],
                'apellido' => $paciente['apellido'],
                'ci' => $paciente['ci'],
                'sexo' => $paciente['sexo'] !== '' ? $paciente['sexo'] : '—',
                'fecha_nacimiento' => $this->fechaLegible($paciente['fecha_nacimiento']),
                'fecha_nacimiento_raw' => $paciente['fecha_nacimiento'] !== ''
                    ? date('Y-m-d', (int) strtotime($paciente['fecha_nacimiento']))
                    : '',
                'edad' => $paciente['edad'] !== null ? $paciente['edad'] . ' año(s)' : '—',
                'telefono' => $paciente['telefono'] !== '' ? $paciente['telefono'] : '—',
                'correo' => $paciente['correo'] !== '' ? $paciente['correo'] : '—',
                'direccion' => $paciente['direccion'] !== '' ? $paciente['direccion'] : '—',
                'estado' => $paciente['estado'],
                'estado_etiqueta' => $paciente['estado'] ? 'Activo' : 'Inactivo',
                'fecha_registro' => $this->fechaLegible($paciente['fecha_registro']),
                'tiene_registro' => $paciente['tiene_registro'],
                'fecha_alta' => $paciente['fecha_alta'] !== '' ? $this->fechaLegible($paciente['fecha_alta']) : null,
                'observaciones' => $paciente['tiene_registro'] && $paciente['observaciones'] !== '' ? $paciente['observaciones'] : null,
                'usuario' => $paciente['usuario'] !== '' ? $paciente['usuario'] : '',
                'tiene_cuenta' => $paciente['tiene_cuenta'],
                'cuenta_correo' => $paciente['cuenta_correo'],
                'bloqueado' => $paciente['bloqueado'],
                'bloqueado_hasta' => $paciente['bloqueado_hasta'],
                'intentos_fallidos' => $paciente['intentos_fallidos'],
                'ultimo_acceso' => $this->fechaLegible($paciente['ultimo_acceso']),
                'fecha_creacion' => $this->fechaLegible($paciente['fecha_creacion']),
                'contactos' => $paciente['contactos'],
            ],
        ]);
    }

    /**
     * Verifica si un usuario de cuenta está disponible (GET, wizard).
     */
    public function usuarioDisponible(Request $request): Response
    {
        $usuario = trim((string) $request->get('usuario', ''));

        if ($usuario === '' || mb_strlen($usuario) > 50) {
            return $this->json(['success' => false, 'disponible' => false, 'message' => 'Usuario inválido.']);
        }

        $disponible = Paciente::usuarioDisponible($usuario);

        return $this->json([
            'success' => true,
            'disponible' => $disponible,
            'message' => $disponible
                ? 'El usuario está disponible.'
                : 'Ese usuario ya está registrado por otro paciente.',
        ]);
    }

    /**
     * Verifica si un CI está disponible (GET, wizard/edición).
     */
    public function ciDisponible(Request $request): Response
    {
        $ci = trim((string) $request->get('ci', ''));
        $excluir = (int) $request->get('excluir', 0);

        if ($ci === '' || mb_strlen($ci) > 15) {
            return $this->json(['success' => false, 'disponible' => false, 'message' => 'CI inválido.']);
        }

        $disponible = Paciente::ciDisponible($ci, $excluir > 0 ? $excluir : null);

        return $this->json([
            'success' => true,
            'disponible' => $disponible,
            'message' => $disponible
                ? 'El CI está disponible.'
                : 'Ese CI ya está registrado en el sistema.',
        ]);
    }

    /**
     * Verifica si un correo está disponible (GET, wizard/edición).
     */
    public function correoDisponible(Request $request): Response
    {
        $correo = trim((string) $request->get('correo', ''));
        $excluir = (int) $request->get('excluir', 0);

        if ($correo === '' || mb_strlen($correo) > 150 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['success' => false, 'disponible' => false, 'message' => 'Correo inválido.']);
        }

        $disponible = Paciente::correoDisponible($correo, $excluir > 0 ? $excluir : null);

        return $this->json([
            'success' => true,
            'disponible' => $disponible,
            'message' => $disponible
                ? 'El correo está disponible.'
                : 'Ese correo ya está registrado en el sistema.',
        ]);
    }

    /**
     * Registra un nuevo paciente (wizard de 2 pasos, POST único).
     */
    public function registrar(Request $request): Response
    {
        if (!$this->csrfValido($request)) {
            return $this->json(['success' => false, 'message' => 'Sesión expirada. Recarga la página.'], 403);
        }

        $ci = trim((string) $request->input('ci', ''));
        $nombre = trim((string) $request->input('nombre', ''));
        $apellido = trim((string) $request->input('apellido', ''));
        $telefono = trim((string) $request->input('telefono', ''));
        $direccion = trim((string) $request->input('direccion', ''));
        $correo = trim((string) $request->input('correo', ''));
        $fechaNacimiento = trim((string) $request->input('fecha_nacimiento', ''));
        $sexo = strtoupper(trim((string) $request->input('sexo', '')));
        $usuario = trim((string) $request->input('usuario', ''));
        $password = (string) $request->input('password', '');
        $passwordConfirm = (string) $request->input('password_confirm', '');

        $errores = [];

        if ($ci === '' || mb_strlen($ci) > 15) {
            $errores['ci'] = 'El CI es obligatorio (máx. 15 caracteres).';
        } elseif (!Paciente::ciDisponible($ci)) {
            $errores['ci'] = 'Ese CI ya está registrado.';
        }

        if ($nombre === '' || mb_strlen($nombre) > 100) {
            $errores['nombre'] = 'El nombre es obligatorio (máx. 100).';
        }

        if ($apellido === '' || mb_strlen($apellido) > 100) {
            $errores['apellido'] = 'El apellido es obligatorio (máx. 100).';
        }

        if ($correo !== '' && (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 150)) {
            $errores['correo'] = 'El correo personal no es válido.';
        } elseif ($correo !== '' && !Paciente::correoDisponible($correo)) {
            $errores['correo'] = 'Ese correo ya está registrado.';
        }

        if ($fechaNacimiento !== '' && !$this->fechaValida($fechaNacimiento)) {
            $errores['fecha_nacimiento'] = 'La fecha de nacimiento no es válida (use YYYY-MM-DD).';
        }

        if ($sexo !== '' && !in_array($sexo, ['MASCULINO', 'FEMENINO', 'OTRO'], true)) {
            $errores['sexo'] = 'El sexo no es válido.';
        }

        if ($usuario === '' || mb_strlen($usuario) > 50) {
            $errores['usuario'] = 'El usuario es obligatorio (máx. 50).';
        } elseif (!Paciente::usuarioDisponible($usuario)) {
            $errores['usuario'] = 'Ese usuario ya está registrado por otro paciente.';
        }

        $correoCuenta = trim((string) $request->input('correo_cuenta', ''));

        if ($correoCuenta === '' || mb_strlen($correoCuenta) > 150 || !filter_var($correoCuenta, FILTER_VALIDATE_EMAIL)) {
            $errores['correo_cuenta'] = 'El correo de la cuenta es obligatorio y debe ser válido.';
        } elseif (!Paciente::correoDisponible($correoCuenta)) {
            $errores['correo_cuenta'] = 'Ese correo de cuenta ya está registrado.';
        }

        if (mb_strlen($password) < 8
            || !preg_match('/[A-Z]/', $password)
            || !preg_match('/[a-z]/', $password)
            || !preg_match('/\d/', $password)
            || !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errores['password'] = 'La contraseña debe tener 8+ caracteres con mayúscula, minúscula, número y símbolo.';
        }

        if ($password !== $passwordConfirm) {
            $errores['password_confirm'] = 'Las contraseñas no coinciden.';
        }

        if ($errores !== []) {
            return $this->json([
                'success' => false,
                'message' => 'Revisa los datos del formulario.',
                'errors' => $errores,
            ], 422);
        }

        try {
            $idPersona = Paciente::crear(
                [
                    'ci' => $ci,
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'telefono' => $telefono,
                    'direccion' => $direccion,
                    'correo' => $correo,
                    'fecha_nacimiento' => $fechaNacimiento,
                    'sexo' => $sexo,
                ],
                [
                    'usuario' => $usuario,
                    'correo' => $correoCuenta,
                    'password_hash' => Hash::make($password),
                ]
            );

            $this->registrarBitacoraAccion(
                $this->idPersona(),
                'persona',
                'INSERT',
                $idPersona,
                [],
                ['ci' => $ci, 'nombre' => $nombre, 'apellido' => $apellido, 'usuario' => $usuario],
                'Alta de nuevo paciente desde el dashboard'
            );

            return $this->json([
                'success' => true,
                'message' => 'Paciente registrado correctamente.',
                'redirect' => route('dashboard_pacientes') ?? '/portal/dashboard/Pacientes',
            ]);
        } catch (\Throwable $e) {
            error_log('Error al registrar paciente: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => 'No se pudo registrar el paciente. Revisa los datos e intenta de nuevo.',
            ], 500);
        }
    }

    /**
     * Actualiza los datos personales de un paciente (POST).
     */
    public function actualizar(Request $request): Response
    {
        if (!$this->csrfValido($request)) {
            return $this->json(['success' => false, 'message' => 'Sesión expirada. Recarga la página.'], 403);
        }

        $id = (int) $request->input('id_persona', 0);

        if ($id <= 0) {
            return $this->json(['success' => false, 'message' => 'Paciente no válido.'], 404);
        }

        $paciente = Paciente::detalle($id);

        if ($paciente === null) {
            return $this->json(['success' => false, 'message' => 'El paciente no existe.'], 404);
        }

        $ci = trim((string) $request->input('ci', ''));
        $nombre = trim((string) $request->input('nombre', ''));
        $apellido = trim((string) $request->input('apellido', ''));
        $telefono = trim((string) $request->input('telefono', ''));
        $direccion = trim((string) $request->input('direccion', ''));
        $correo = trim((string) $request->input('correo', ''));
        $fechaNacimiento = trim((string) $request->input('fecha_nacimiento', ''));
        $sexo = strtoupper(trim((string) $request->input('sexo', '')));
        $estado = (string) $request->input('estado', '1') === '1';

        $errores = [];

        if ($ci === '' || mb_strlen($ci) > 15) {
            $errores['ci'] = 'El CI es obligatorio (máx. 15 caracteres).';
        } elseif (!Paciente::ciDisponible($ci, $id)) {
            $errores['ci'] = 'Ese CI ya está registrado por otra persona.';
        }

        if ($nombre === '' || mb_strlen($nombre) > 100) {
            $errores['nombre'] = 'El nombre es obligatorio (máx. 100).';
        }

        if ($apellido === '' || mb_strlen($apellido) > 100) {
            $errores['apellido'] = 'El apellido es obligatorio (máx. 100).';
        }

        if ($correo !== '' && (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 150)) {
            $errores['correo'] = 'El correo no es válido.';
        } elseif ($correo !== '' && !Paciente::correoDisponible($correo, $id)) {
            $errores['correo'] = 'Ese correo ya está registrado.';
        }

        if ($fechaNacimiento !== '' && !$this->fechaValida($fechaNacimiento)) {
            $errores['fecha_nacimiento'] = 'La fecha de nacimiento no es válida (use YYYY-MM-DD).';
        }

        if ($sexo !== '' && !in_array($sexo, ['MASCULINO', 'FEMENINO', 'OTRO'], true)) {
            $errores['sexo'] = 'El sexo no es válido.';
        }

        if ($errores !== []) {
            return $this->json([
                'success' => false,
                'message' => 'Revisa los datos del formulario.',
                'errors' => $errores,
            ], 422);
        }

        $antes = [
            'ci' => $paciente['ci'],
            'nombre' => $paciente['nombre'],
            'apellido' => $paciente['apellido'],
            'telefono' => $paciente['telefono'],
            'direccion' => $paciente['direccion'],
            'correo' => $paciente['correo'],
            'fecha_nacimiento' => $paciente['fecha_nacimiento'],
            'sexo' => $paciente['sexo'],
            'estado' => $paciente['estado'],
        ];

        try {
            Paciente::actualizarDatos($id, [
                'ci' => $ci,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'correo' => $correo,
                'fecha_nacimiento' => $fechaNacimiento,
                'sexo' => $sexo,
                'estado' => $estado,
            ]);

            $this->registrarBitacoraAccion(
                $this->idPersona(),
                'persona',
                'UPDATE',
                $id,
                $antes,
                ['ci' => $ci, 'nombre' => $nombre, 'apellido' => $apellido, 'estado' => $estado],
                'Actualización de datos personales de paciente'
            );

            return $this->json([
                'success' => true,
                'message' => 'Datos del paciente actualizados correctamente.',
            ]);
        } catch (\Throwable $e) {
            error_log('Error al actualizar paciente: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => 'No se pudieron actualizar los datos. Intenta de nuevo.',
            ], 500);
        }
    }

    /**
     * Registra un contacto de emergencia para un paciente (POST).
     */
    public function guardarContacto(Request $request): Response
    {
        if (!$this->csrfValido($request)) {
            return $this->json(['success' => false, 'message' => 'Sesión expirada. Recarga la página.'], 403);
        }

        $id = (int) $request->input('id_paciente', 0);

        if ($id <= 0 || Paciente::detalle($id) === null) {
            return $this->json(['success' => false, 'message' => 'El paciente no existe.'], 404);
        }

        $nombre = trim((string) $request->input('nombre', ''));
        $parentesco = trim((string) $request->input('parentesco', ''));
        $telefono = trim((string) $request->input('telefono', ''));
        $correo = trim((string) $request->input('correo', ''));

        $errores = [];

        if ($nombre === '' || mb_strlen($nombre) > 100) {
            $errores['nombre'] = 'El nombre es obligatorio (máx. 100).';
        }

        if ($parentesco === '' || mb_strlen($parentesco) > 50) {
            $errores['parentesco'] = 'El parentesco es obligatorio (máx. 50).';
        }

        if ($telefono === '' || mb_strlen($telefono) > 15) {
            $errores['telefono'] = 'El teléfono es obligatorio (máx. 15).';
        }

        if ($correo !== '' && (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 150)) {
            $errores['correo'] = 'El correo no es válido.';
        }

        if ($errores !== []) {
            return $this->json([
                'success' => false,
                'message' => 'Revisa los datos del formulario.',
                'errors' => $errores,
            ], 422);
        }

        try {
            $idContacto = Paciente::guardarContacto($id, [
                'nombre' => $nombre,
                'parentesco' => $parentesco,
                'telefono' => $telefono,
                'correo' => $correo,
                'estado' => true,
            ]);

            $this->registrarBitacoraAccion(
                $this->idPersona(),
                'contacto_emergencia',
                'INSERT',
                $idContacto,
                [],
                ['id_paciente' => $id, 'nombre' => $nombre, 'telefono' => $telefono],
                'Alta de contacto de emergencia de paciente'
            );

            return $this->json([
                'success' => true,
                'message' => 'Contacto de emergencia registrado correctamente.',
            ]);
        } catch (\Throwable $e) {
            error_log('Error al guardar contacto de emergencia: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => 'No se pudo registrar el contacto. Intenta de nuevo.',
            ], 500);
        }
    }

    /**
     * Cambia el estado de la persona (baja o reactivación) (POST).
     */
    public function cambiarEstado(Request $request): Response
    {
        if (!$this->csrfValido($request)) {
            return $this->json(['success' => false, 'message' => 'Sesión expirada. Recarga la página.'], 403);
        }

        $id = (int) $request->input('id_persona', 0);
        $estado = (string) $request->input('estado', '') === '1';

        if ($id <= 0) {
            return $this->json(['success' => false, 'message' => 'Paciente no válido.'], 404);
        }

        $paciente = Paciente::detalle($id);

        if ($paciente === null) {
            return $this->json(['success' => false, 'message' => 'El paciente no existe.'], 404);
        }

        if ($paciente['estado'] === $estado) {
            return $this->json([
                'success' => true,
                'message' => 'El paciente ya se encuentra en ese estado.',
            ]);
        }

        try {
            Paciente::cambiarEstado($id, $estado);

            $this->registrarBitacoraAccion(
                $this->idPersona(),
                'persona',
                'UPDATE',
                $id,
                ['estado' => $paciente['estado']],
                ['estado' => $estado],
                $estado
                    ? 'Reactivación de paciente'
                    : 'Baja de paciente (estado inactivo)'
            );

            return $this->json([
                'success' => true,
                'message' => $estado
                    ? 'Paciente reactivado correctamente.'
                    : 'Paciente dado de baja correctamente.',
            ]);
        } catch (\Throwable $e) {
            error_log('Error al cambiar estado del paciente: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => 'No se pudo cambiar el estado del paciente. Intenta de nuevo.',
            ], 500);
        }
    }

    /**
     * Genera un PDF (dompdf) con todos los pacientes que coincidan con
     * los filtros actuales, sin paginación.
     */
    public function pdf(Request $request): Response
    {
        $filtros = $this->filtros($request);

        $pacientes = Paciente::listarTodos($filtros);
        $config = \App\Models\ConfiguracionSistema::publica();

        $html = View::render('portal.pacientes.pdf', [
            'pacientes' => $pacientes,
            'filtros' => $filtros,
            'total' => count($pacientes),
            'hoy' => date('d/m/Y'),
            'hora' => date('H:i'),
            'config' => $config,
        ]);

        require_once BASE_PATH . '/resources/dompdf/autoload.inc.php';

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', BASE_PATH);
        $options->set('dpi', 120);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $canvas->page_text(40, 812, 'Reporte de pacientes · ' . (string) ($config['clinica_nombre'] ?? 'Clínica Vida Salud'), null, 7, [0.45, 0.45, 0.45]);
        $canvas->page_text(430, 812, 'Página {PAGE_NUM} de {PAGE_COUNT}', null, 7, [0.45, 0.45, 0.45]);

        return Response::make($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="pacientes-' . date('Ymd-His') . '.pdf"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Genera un CSV con todos los pacientes que coincidan con los
     * filtros actuales, sin paginación: mismos datos y filtros que pdf().
     */
    public function csv(Request $request): Response
    {
        $filtros = $this->filtros($request);
        $pacientes = Paciente::listarTodos($filtros);

        $encabezados = [
            'nombre_completo' => 'Paciente',
            'ci' => 'CI',
            'sexo' => 'Sexo',
            'edad' => 'Edad',
            'correo' => 'Correo',
            'estado' => 'Estado',
            'fecha_registro' => 'Fecha de registro',
        ];

        $filas = [];

        foreach ($pacientes as $paciente) {
            $filas[] = [
                'nombre_completo' => (string) ($paciente['nombre_completo'] ?? ''),
                'ci' => (string) ($paciente['ci'] ?? ''),
                'sexo' => (string) ($paciente['sexo'] ?? ''),
                'edad' => $paciente['edad'] ?? null,
                'correo' => (string) ($paciente['correo'] ?? ''),
                'estado' => (bool) ($paciente['estado'] ?? false) ? 'Activo' : 'Inactivo',
                'fecha_registro' => (string) ($paciente['fecha_registro'] ?? ''),
            ];
        }

        return Csv::descargar('pacientes-' . date('Ymd-His') . '.csv', $encabezados, $filas);
    }

    /**
     * Valida el token CSRF para peticiones POST (body o header).
     */
    private function csrfValido(Request $request): bool
    {
        $token = (string) ($request->input('_token') ?? $request->header('X-CSRF-TOKEN', ''));

        return Csrf::validate($token);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Normaliza los filtros de búsqueda.
     *
     * @return array{nombre: string, ci: string, sexo: string, estado: string, regular: string, desde: string, hasta: string}
     */
    private function filtros(Request $request): array
    {
        $nombre = trim((string) $request->get('nombre', ''));
        $ci = trim((string) $request->get('ci', ''));
        $sexo = strtoupper(trim((string) $request->get('sexo', '')));
        $estado = trim((string) $request->get('estado', ''));
        $regular = trim((string) $request->get('regular', ''));
        $desde = trim((string) $request->get('desde', ''));
        $hasta = trim((string) $request->get('hasta', ''));

        if ($nombre !== '' && mb_strlen($nombre) > 100) {
            $nombre = mb_substr($nombre, 0, 100);
        }

        if ($ci !== '' && mb_strlen($ci) > 15) {
            $ci = mb_substr($ci, 0, 15);
        }

        if (!in_array($sexo, ['MASCULINO', 'FEMENINO', 'OTRO'], true)) {
            $sexo = '';
        }

        if (!in_array($estado, ['1', '0'], true)) {
            $estado = '';
        }

        if (!in_array($regular, ['1', '0'], true)) {
            $regular = '';
        }

        if (!$this->fechaValida($desde)) {
            $desde = '';
        }

        if (!$this->fechaValida($hasta)) {
            $hasta = '';
        }

        return [
            'nombre' => $nombre,
            'ci' => $ci,
            'sexo' => $sexo,
            'estado' => $estado,
            'regular' => $regular,
            'desde' => $desde,
            'hasta' => $hasta,
        ];
    }

    /**
     * Formatea una fecha (o fecha y hora) a d/m/Y [H:i:s].
     */
    private function fechaLegible(string $fecha): string
    {
        if ($fecha === '') {
            return '—';
        }

        $timestamp = strtotime($fecha);

        if ($timestamp === false) {
            return $fecha;
        }

        $hora = date('H:i:s', $timestamp);

        return $hora === '00:00:00'
            ? date('d/m/Y', $timestamp)
            : date('d/m/Y H:i:s', $timestamp);
    }

    private function nombre(): string
    {
        return (string) \Lib\Support\Session::get('portal_nombre', 'Usuario');
    }
}
