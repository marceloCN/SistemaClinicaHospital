<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Persona;
use App\Controllers\Controller;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\View;
use Lib\Support\Csrf;
use Lib\Support\Csv;
use Lib\Support\Hash;

/**
 * Dashboard de empleados del sistema (módulo administrativo).
 *
 * Administra las personas con rol ADMIN, RECEP o MEDICO (cualquier rol
 * distinto de PAC): resumen, listado paginado con filtros, registro en
 * 2 pasos (datos + cuenta con rol y ficha de médico), edición, cambio
 * de estado y reporte PDF.
 */
class PortalUsuarioController extends Controller
{
    private const POR_PAGINA = 10;

    private const ROLES = ['ADMIN', 'RECEP', 'MEDICO'];

    /**
     * Pantalla del dashboard de empleados.
     */
    public function index(Request $request): Response
    {
        $filtros = $this->filtros($request);
        $pagina = max(1, (int) $request->get('page', 1));

        $tab = (string) $request->get('tab', 'resumen');

        if (!in_array($tab, ['resumen', 'datos'], true)) {
            $tab = 'resumen';
        }

        $listado = Persona::listarPersonal($filtros, $pagina, self::POR_PAGINA);
        $estadisticas = Persona::estadisticasPersonal();
        $recientes = Persona::recientesPersonal(10);
        $roles = Persona::rolesDisponibles();

        return $this->view('portal.base.layout', [
            'rolClave' => $this->rolClave(),
            'nombre' => $this->nombre(),
            'active' => 'usuarios',
            'content' => View::render('portal.usuarios.index', [
                'personal' => $listado['items'],
                'total' => $listado['total'],
                'pagina' => $listado['pagina'],
                'paginas' => $listado['paginas'],
                'porPagina' => $listado['porPagina'],
                'numerosPagina' => $this->numerosPagina($listado['pagina'], $listado['paginas']),
                'estadisticas' => $estadisticas,
                'recientes' => $recientes,
                'roles' => $roles,
                'idActual' => $this->idPersona(),
                'tab' => $tab,
                'filtros' => $filtros,
                'filtroNombre' => (string) ($filtros['nombre'] ?? ''),
                'filtroCi' => (string) ($filtros['ci'] ?? ''),
                'filtroRol' => (string) ($filtros['rol'] ?? ''),
                'filtroEstado' => (string) ($filtros['estado'] ?? ''),
                'filtroDesde' => (string) ($filtros['desde'] ?? ''),
                'filtroHasta' => (string) ($filtros['hasta'] ?? ''),
            ]),
        ]);
    }

    /**
     * Detalle de un empleado (carga AJAX en el modal): datos, cuenta y
     * ficha de médico cuando aplica.
     */
    public function detalle(Request $request, int $id): Response
    {
        $empleado = Persona::detallePersonal($id);

        if ($empleado === null) {
            return $this->json([
                'success' => false,
                'message' => 'El empleado no existe.',
            ], 404);
        }

        return $this->json([
            'success' => true,
            'empleado' => [
                'id_persona' => $empleado['id_persona'],
                'nombre_completo' => $empleado['nombre_completo'],
                'nombre' => $empleado['nombre'],
                'apellido' => $empleado['apellido'],
                'ci' => $empleado['ci'],
                'sexo' => $empleado['sexo'] !== '' ? $empleado['sexo'] : '—',
                'fecha_nacimiento' => $this->fechaLegible($empleado['fecha_nacimiento']),
                'fecha_nacimiento_raw' => $empleado['fecha_nacimiento'] !== ''
                    ? date('Y-m-d', (int) strtotime($empleado['fecha_nacimiento']))
                    : '',
                'edad' => $empleado['edad'] !== null ? $empleado['edad'] . ' año(s)' : '—',
                'telefono' => $empleado['telefono'] !== '' ? $empleado['telefono'] : '—',
                'correo' => $empleado['correo'] !== '' ? $empleado['correo'] : '—',
                'direccion' => $empleado['direccion'] !== '' ? $empleado['direccion'] : '—',
                'estado' => $empleado['estado'],
                'estado_etiqueta' => $empleado['estado'] ? 'Activo' : 'Inactivo',
                'fecha_registro' => $this->fechaLegible($empleado['fecha_registro']),
                'id_rol' => $empleado['id_rol'],
                'rol_clave' => $empleado['rol_clave'],
                'rol_nombre' => $empleado['rol_nombre'],
                'usuario' => $empleado['usuario'] !== '' ? $empleado['usuario'] : '',
                'tiene_cuenta' => $empleado['tiene_cuenta'],
                'cuenta_correo' => $empleado['cuenta_correo'],
                'bloqueado' => $empleado['bloqueado'],
                'bloqueado_hasta' => $empleado['bloqueado_hasta'],
                'intentos_fallidos' => $empleado['intentos_fallidos'],
                'ultimo_acceso' => $this->fechaLegible($empleado['ultimo_acceso']),
                'fecha_creacion' => $this->fechaLegible($empleado['fecha_creacion']),
                'es_medico' => $empleado['es_medico'],
                'nro_colegiado' => $empleado['nro_colegiado'],
                'fecha_ingreso' => $empleado['fecha_ingreso'] !== ''
                    ? date('Y-m-d', (int) strtotime($empleado['fecha_ingreso']))
                    : '',
                'fecha_ingreso_legible' => $empleado['fecha_ingreso'] !== ''
                    ? $this->fechaLegible($empleado['fecha_ingreso'])
                    : null,
                'biografia' => $empleado['biografia'],
                'medico_estado' => $empleado['medico_estado'],
            ],
        ]);
    }

    /**
     * Verifica si un usuario de cuenta está disponible (GET, wizard).
     */
    public function usuarioDisponible(Request $request): Response
    {
        $usuario = trim((string) $request->get('usuario', ''));
        $excluir = (int) $request->get('excluir', 0);

        if ($usuario === '' || mb_strlen($usuario) > 50) {
            return $this->json(['success' => false, 'disponible' => false, 'message' => 'Usuario inválido.']);
        }

        $disponible = Persona::usuarioDisponible($usuario, $excluir > 0 ? $excluir : null);

        return $this->json([
            'success' => true,
            'disponible' => $disponible,
            'message' => $disponible
                ? 'El usuario está disponible.'
                : 'Ese usuario ya está registrado por otra persona.',
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

        $disponible = Persona::ciDisponible($ci, $excluir > 0 ? $excluir : null);

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

        $disponible = Persona::correoDisponible($correo, $excluir > 0 ? $excluir : null);

        return $this->json([
            'success' => true,
            'disponible' => $disponible,
            'message' => $disponible
                ? 'El correo está disponible.'
                : 'Ese correo ya está registrado en el sistema.',
        ]);
    }

    /**
     * Registra un nuevo empleado (wizard de 2 pasos, POST único).
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
        $idRol = (int) $request->input('id_rol', 0);
        $usuario = trim((string) $request->input('usuario', ''));
        $password = (string) $request->input('password', '');
        $passwordConfirm = (string) $request->input('password_confirm', '');
        $nroColegiado = trim((string) $request->input('nro_colegiado', ''));
        $fechaIngreso = trim((string) $request->input('fecha_ingreso', ''));
        $biografia = trim((string) $request->input('biografia', ''));

        $errores = [];

        if ($ci === '' || mb_strlen($ci) > 15) {
            $errores['ci'] = 'El CI es obligatorio (máx. 15 caracteres).';
        } elseif (!Persona::ciDisponible($ci)) {
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
        } elseif ($correo !== '' && !Persona::correoDisponible($correo)) {
            $errores['correo'] = 'Ese correo ya está registrado.';
        }

        if ($fechaNacimiento !== '' && !$this->fechaValida($fechaNacimiento)) {
            $errores['fecha_nacimiento'] = 'La fecha de nacimiento no es válida (use YYYY-MM-DD).';
        }

        if ($sexo !== '' && !in_array($sexo, ['MASCULINO', 'FEMENINO', 'OTRO'], true)) {
            $errores['sexo'] = 'El sexo no es válido.';
        }

        $claveRol = $idRol > 0 ? Persona::claveRol($idRol) : null;

        if ($claveRol === null) {
            $errores['id_rol'] = 'Selecciona un rol válido (administrador, recepcionista o médico).';
        }

        if ($usuario === '' || mb_strlen($usuario) > 50) {
            $errores['usuario'] = 'El usuario es obligatorio (máx. 50).';
        } elseif (!Persona::usuarioDisponible($usuario)) {
            $errores['usuario'] = 'Ese usuario ya está registrado por otra persona.';
        }

        $correoCuenta = trim((string) $request->input('correo_cuenta', ''));

        if ($correoCuenta === '' || mb_strlen($correoCuenta) > 150 || !filter_var($correoCuenta, FILTER_VALIDATE_EMAIL)) {
            $errores['correo_cuenta'] = 'El correo de la cuenta es obligatorio y debe ser válido.';
        } elseif (!Persona::correoDisponible($correoCuenta)) {
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

        // Ficha de médico: requerida solo cuando el rol elegido es MEDICO.
        if ($claveRol === 'MEDICO') {
            if ($nroColegiado === '' || mb_strlen($nroColegiado) > 50) {
                $errores['nro_colegiado'] = 'El número de colegiado es obligatorio (máx. 30).';
            }

            if ($fechaIngreso === '') {
                $errores['fecha_ingreso'] = 'La fecha de ingreso es obligatoria.';
            } elseif (!$this->fechaValida($fechaIngreso)) {
                $errores['fecha_ingreso'] = 'La fecha de ingreso no es válida (use YYYY-MM-DD).';
            }
        }

        if ($errores !== []) {
            return $this->json([
                'success' => false,
                'message' => 'Revisa los datos del formulario.',
                'errors' => $errores,
            ], 422);
        }

        try {
            $idPersona = Persona::crearPersonal(
                [
                    'ci' => $ci,
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'telefono' => $telefono,
                    'direccion' => $direccion,
                    'correo' => $correo,
                    'fecha_nacimiento' => $fechaNacimiento,
                    'sexo' => $sexo,
                    'id_rol' => $idRol,
                ],
                [
                    'usuario' => $usuario,
                    'correo' => $correoCuenta,
                    'password_hash' => Hash::make($password),
                ],
                [
                    'nro_colegiado' => $nroColegiado,
                    'fecha_ingreso' => $fechaIngreso !== '' ? $fechaIngreso : date('Y-m-d'),
                    'biografia' => $biografia,
                ]
            );

            $this->registrarBitacoraAccion(
                $this->idPersona(),
                'persona',
                'INSERT',
                $idPersona,
                [],
                [
                    'ci' => $ci,
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'usuario' => $usuario,
                    'rol' => $claveRol,
                ],
                'Alta de empleado del sistema desde el dashboard'
            );

            return $this->json([
                'success' => true,
                'message' => 'Empleado registrado correctamente.',
                'redirect' => route('dashboard_usuarios') ?? '/portal/dashboard/Usuarios',
            ]);
        } catch (\Throwable $e) {
            error_log('Error al registrar empleado: ' . $e->getMessage());

            $mensaje = 'No se pudo registrar el empleado. Revisa los datos e intenta de nuevo.';

            if (config('app.debug')) {
                $mensaje .= ' [debug] ' . $e->getMessage();
            }

            return $this->json([
                'success' => false,
                'message' => $mensaje,
            ], 500);
        }
    }

    /**
     * Actualiza los datos personales de un empleado (POST).
     *
     * No modifica el CI ni el estado; está prohibido autoeditarse (usa el
     * Perfil) y no se pueden editar empleados dados de baja. Si el rol es
     * MEDICO también se guarda su ficha en la tabla medico.
     */
    public function actualizar(Request $request): Response
    {
        if (!$this->csrfValido($request)) {
            return $this->json(['success' => false, 'message' => 'Sesión expirada. Recarga la página.'], 403);
        }

        $id = (int) $request->input('id_persona', 0);

        if ($id <= 0) {
            return $this->json(['success' => false, 'message' => 'Empleado no válido.'], 404);
        }

        if ($id === $this->idPersona()) {
            return $this->json([
                'success' => false,
                'message' => 'No puedes modificar tu propia cuenta desde este módulo; usa la sección Perfil.',
            ], 403);
        }

        $empleado = Persona::detallePersonal($id);

        if ($empleado === null) {
            return $this->json(['success' => false, 'message' => 'El empleado no existe.'], 404);
        }

        if (!$empleado['estado']) {
            return $this->json([
                'success' => false,
                'message' => 'El empleado está dado de baja: reactívalo para poder modificar sus datos.',
            ], 422);
        }

        $ci = trim((string) $request->input('ci', $empleado['ci']));
        $nombre = trim((string) $request->input('nombre', ''));
        $apellido = trim((string) $request->input('apellido', ''));
        $telefono = trim((string) $request->input('telefono', ''));
        $direccion = trim((string) $request->input('direccion', ''));
        $correo = trim((string) $request->input('correo', ''));
        $fechaNacimiento = trim((string) $request->input('fecha_nacimiento', ''));
        $sexo = strtoupper(trim((string) $request->input('sexo', '')));
        $idRol = (int) $request->input('id_rol', $empleado['id_rol']);
        $nroColegiado = trim((string) $request->input('nro_colegiado', ''));
        $fechaIngreso = trim((string) $request->input('fecha_ingreso', ''));
        $biografia = (string) $request->input('biografia', '');

        $errores = [];

        if ($ci === '' || mb_strlen($ci) > 15) {
            $errores['ci'] = 'El CI es obligatorio (máx. 15 caracteres).';
        } elseif (!Persona::ciDisponible($ci, $id)) {
            $errores['ci'] = 'Ese CI ya está registrado.';
        }

        if ($nombre === '' || mb_strlen($nombre) > 100) {
            $errores['nombre'] = 'El nombre es obligatorio (máx. 100).';
        }

        if ($apellido === '' || mb_strlen($apellido) > 100) {
            $errores['apellido'] = 'El apellido es obligatorio (máx. 100).';
        }

        if ($correo !== '' && (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 150)) {
            $errores['correo'] = 'El correo no es válido.';
        } elseif ($correo !== '' && !Persona::correoDisponible($correo, $id)) {
            $errores['correo'] = 'Ese correo ya está registrado.';
        }

        if ($fechaNacimiento !== '' && !$this->fechaValida($fechaNacimiento)) {
            $errores['fecha_nacimiento'] = 'La fecha de nacimiento no es válida (use YYYY-MM-DD).';
        }

        if ($sexo !== '' && !in_array($sexo, ['MASCULINO', 'FEMENINO', 'OTRO'], true)) {
            $errores['sexo'] = 'El sexo no es válido.';
        }

        $claveRol = Persona::claveRol($idRol);

        if ($claveRol === null) {
            $errores['id_rol'] = 'Selecciona un rol válido.';
        } elseif ($claveRol === 'MEDICO') {
            // Ficha médica: obligatoria mientras el rol sea Médico.
            if ($nroColegiado === '' || mb_strlen($nroColegiado) > 50) {
                $errores['nro_colegiado'] = 'El número de colegiado es obligatorio (máx. 50).';
            }

            if ($fechaIngreso === '') {
                $errores['fecha_ingreso'] = 'La fecha de ingreso es obligatoria.';
            } elseif (!$this->fechaValida($fechaIngreso)) {
                $errores['fecha_ingreso'] = 'La fecha de ingreso no es válida (use YYYY-MM-DD).';
            }
        }

        if ($errores !== []) {
            return $this->json([
                'success' => false,
                'message' => 'Revisa los datos del formulario.',
                'errors' => $errores,
            ], 422);
        }

        $antes = [
            'ci' => $empleado['ci'],
            'nombre' => $empleado['nombre'],
            'apellido' => $empleado['apellido'],
            'telefono' => $empleado['telefono'],
            'direccion' => $empleado['direccion'],
            'correo' => $empleado['correo'],
            'fecha_nacimiento' => $empleado['fecha_nacimiento'],
            'sexo' => $empleado['sexo'],
            'id_rol' => $empleado['id_rol'],
            'nro_colegiado' => $empleado['nro_colegiado'],
            'fecha_ingreso' => $empleado['fecha_ingreso'],
            'biografia' => $empleado['biografia'],
        ];

        try {
            Persona::actualizarPersonal($id, [
                'ci' => $ci,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'correo' => $correo,
                'fecha_nacimiento' => $fechaNacimiento,
                'sexo' => $sexo,
                'estado' => $empleado['estado'],
                'id_rol' => $idRol,
            ]);

            if ($claveRol === 'MEDICO') {
                Persona::actualizarFichaMedico($id, [
                    'nro_colegiado' => $nroColegiado,
                    'fecha_ingreso' => $fechaIngreso,
                    'biografia' => $biografia,
                ]);
            }

            $this->registrarBitacoraAccion(
                $this->idPersona(),
                'persona',
                'UPDATE',
                $id,
                $antes,
                [
                    'ci' => $ci,
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'id_rol' => $idRol,
                    'nro_colegiado' => $claveRol === 'MEDICO' ? $nroColegiado : null,
                    'biografia' => $claveRol === 'MEDICO' ? $biografia : null,
                ],
                'Actualización de datos de empleado del sistema'
            );

            return $this->json([
                'success' => true,
                'message' => 'Datos del empleado actualizados correctamente.',
            ]);
        } catch (\Throwable $e) {
            error_log('Error al actualizar empleado: ' . $e->getMessage());

            $mensaje = 'No se pudieron actualizar los datos. Intenta de nuevo.';

            if (config('app.debug')) {
                $mensaje .= ' [debug] ' . $e->getMessage();
            }

            return $this->json([
                'success' => false,
                'message' => $mensaje,
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
            return $this->json(['success' => false, 'message' => 'Empleado no válido.'], 404);
        }

        if ($id === $this->idPersona()) {
            return $this->json([
                'success' => false,
                'message' => 'No puedes darte de baja a ti mismo; pide a otro administrador que lo haga.',
            ], 422);
        }

        $empleado = Persona::detallePersonal($id);

        if ($empleado === null) {
            return $this->json(['success' => false, 'message' => 'El empleado no existe.'], 404);
        }

        if ($empleado['estado'] === $estado) {
            return $this->json([
                'success' => true,
                'message' => 'El empleado ya se encuentra en ese estado.',
            ]);
        }

        try {
            Persona::cambiarEstado($id, $estado);

            $this->registrarBitacoraAccion(
                $this->idPersona(),
                'persona',
                'UPDATE',
                $id,
                ['estado' => $empleado['estado']],
                ['estado' => $estado],
                $estado
                    ? 'Reactivación de empleado'
                    : 'Baja de empleado (estado inactivo)'
            );

            return $this->json([
                'success' => true,
                'message' => $estado
                    ? 'Empleado reactivado correctamente.'
                    : 'Empleado dado de baja correctamente.',
            ]);
        } catch (\Throwable $e) {
            error_log('Error al cambiar estado del empleado: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => 'No se pudo cambiar el estado del empleado. Intenta de nuevo.',
            ], 500);
        }
    }

    /**
     * Genera un PDF (dompdf) con todos los empleados que coincidan con
     * los filtros actuales, sin paginación.
     */
    public function pdf(Request $request): Response
    {
        $filtros = $this->filtros($request);

        $personal = Persona::listarPersonalTodos($filtros);
        $config = \App\Models\ConfiguracionSistema::publica();

        $html = View::render('portal.usuarios.pdf', [
            'personal' => $personal,
            'filtros' => $filtros,
            'total' => count($personal),
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
        $canvas->page_text(40, 812, 'Reporte de empleados · ' . (string) ($config['clinica_nombre'] ?? 'Clínica Vida Salud'), null, 7, [0.45, 0.45, 0.45]);
        $canvas->page_text(430, 812, 'Página {PAGE_NUM} de {PAGE_COUNT}', null, 7, [0.45, 0.45, 0.45]);

        return Response::make($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="empleados-' . date('Ymd-His') . '.pdf"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Genera un CSV con todos los empleados que coincidan con los
     * filtros actuales, sin paginación: mismos datos y filtros que pdf().
     */
    public function csv(Request $request): Response
    {
        $filtros = $this->filtros($request);
        $personal = Persona::listarPersonalTodos($filtros);

        $encabezados = [
            'nombre_completo' => 'Empleado',
            'correo' => 'Correo',
            'ci' => 'CI',
            'rol_nombre' => 'Rol',
            'usuario' => 'Usuario',
            'estado' => 'Estado',
            'fecha_registro' => 'Fecha de registro',
        ];

        $filas = [];

        foreach ($personal as $empleado) {
            $filas[] = [
                'nombre_completo' => (string) ($empleado['nombre_completo'] ?? ''),
                'correo' => (string) ($empleado['correo'] ?? ''),
                'ci' => (string) ($empleado['ci'] ?? ''),
                'rol_nombre' => (string) ($empleado['rol_nombre'] ?? ''),
                'usuario' => (string) ($empleado['usuario'] ?? ''),
                'estado' => (bool) ($empleado['estado'] ?? false) ? 'Activo' : 'Inactivo',
                'fecha_registro' => (string) ($empleado['fecha_registro'] ?? ''),
            ];
        }

        return Csv::descargar('empleados-' . date('Ymd-His') . '.csv', $encabezados, $filas);
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
     * @return array{nombre: string, ci: string, rol: string, estado: string, desde: string, hasta: string}
     */
    private function filtros(Request $request): array
    {
        $nombre = trim((string) $request->get('nombre', ''));
        $ci = trim((string) $request->get('ci', ''));
        $rol = strtoupper(trim((string) $request->get('rol', '')));
        $estado = trim((string) $request->get('estado', ''));
        $desde = trim((string) $request->get('desde', ''));
        $hasta = trim((string) $request->get('hasta', ''));

        if ($nombre !== '' && mb_strlen($nombre) > 100) {
            $nombre = mb_substr($nombre, 0, 100);
        }

        if ($ci !== '' && mb_strlen($ci) > 15) {
            $ci = mb_substr($ci, 0, 15);
        }

        if (!in_array($rol, self::ROLES, true)) {
            $rol = '';
        }

        if (!in_array($estado, ['1', '0'], true)) {
            $estado = '';
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
            'rol' => $rol,
            'estado' => $estado,
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
