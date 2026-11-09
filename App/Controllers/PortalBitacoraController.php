<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\BitacoraAcceso;
use App\Models\BitacoraAccion;
use App\Models\ConfiguracionSistema;
use App\Models\CuentaPersona;
use App\Controllers\Controller;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\View;
use Lib\Support\Csv;

/**
 * Bitácora de accesos al sistema.
 *
 * Lista los accesos registrados en bitacora_accesos con filtros por persona
 * y rango de fechas, paginados de 10 en 10, y permite ver el detalle de
 * cada acceso y exportar la página actual en PDF.
 */
class PortalBitacoraController extends Controller
{
    private const POR_PAGINA = 10;

    /**
     * Pantalla con los filtros y la lista paginada de accesos.
     */
    public function index(Request $request): Response
    {
        $filtros = $this->filtros($request);
        $pagina = max(1, (int) $request->get('page', 1));

        $listado = BitacoraAcceso::listar($filtros, $pagina, self::POR_PAGINA);

        return $this->view('portal.base.layout', [
            'rolClave' => $this->rolClave(),
            'nombre' => $this->nombre(),
            'active' => 'bitacora',
            'content' => View::render('portal.bitacora.index', [
                'accesos' => $listado['items'],
                'total' => $listado['total'],
                'pagina' => $listado['pagina'],
                'paginas' => $listado['paginas'],
                'porPagina' => $listado['porPagina'],
                'numerosPagina' => $this->numerosPagina($listado['pagina'], $listado['paginas']),
                'filtros' => $filtros,
                'filtroNombre' => (string) ($filtros['nombre'] ?? ''),
                'filtroDesde' => (string) ($filtros['desde'] ?? ''),
                'filtroHasta' => (string) ($filtros['hasta'] ?? ''),
            ]),
        ]);
    }

    /**
     * Detalle de un acceso (se carga por AJAX en el modal).
     */
    public function detalle(Request $request, int $id): Response
    {        $acceso = BitacoraAcceso::detalle($id);

        if ($acceso === null) {
            return $this->json([
                'success' => false,
                'message' => 'El registro de acceso no existe.',
            ], 404);
        }

        $cuenta = $acceso['id_persona'] !== null
            ? CuentaPersona::query()->where('id_persona', $acceso['id_persona'])->first()
            : null;

        return $this->json([
            'success' => true,
            'acceso' => [
                'id' => $acceso['id'],
                'fecha_hora' => $acceso['fecha_hora'],
                'fecha_hora_legible' => $this->fechaLegible($acceso['fecha_hora']),
                'nombre_completo' => $acceso['nombre_completo'],
                'usuario' => $cuenta !== null ? (string) $cuenta['usuario'] : '',
                'rol' => $acceso['rol_nombre'] !== '' ? $acceso['rol_nombre'] : '—',
                'rol_clave' => $acceso['rol_clave'],
                'usuario_intentado' => $acceso['usuario_intentado'],
                'estado' => $acceso['estado'],
                'estado_etiqueta' => BitacoraAcceso::etiquetaEstado($acceso['estado']),
                'resultado' => $this->resultadoAcceso($acceso['estado']),
                'resultado_etiqueta' => $this->resultadoAcceso($acceso['estado']) === 'EXITOSO' ? 'Exitoso' : 'Fallido',
                'detalle' => $acceso['detalle'],
                'ip_address' => $acceso['ip_address'],
                'user_agent' => $acceso['user_agent'],
            ],
        ]);
    }

    /**
     * Pantalla con los filtros y la lista paginada de acciones.
     */
    public function acciones(Request $request): Response
    {
        $filtros = $this->filtros($request);
        $pagina = max(1, (int) $request->get('page', 1));

        $listado = BitacoraAccion::listar($filtros, $pagina, self::POR_PAGINA);

        return $this->view('portal.base.layout', [
            'rolClave' => $this->rolClave(),
            'nombre' => $this->nombre(),
            'active' => 'bitacora_accion',
            'content' => View::render('portal.bitacora.acciones', [
                'acciones' => $listado['items'],
                'total' => $listado['total'],
                'pagina' => $listado['pagina'],
                'paginas' => $listado['paginas'],
                'porPagina' => $listado['porPagina'],
                'numerosPagina' => $this->numerosPagina($listado['pagina'], $listado['paginas']),
                'filtroNombre' => (string) ($filtros['nombre'] ?? ''),
                'filtroDesde' => (string) ($filtros['desde'] ?? ''),
                'filtroHasta' => (string) ($filtros['hasta'] ?? ''),
            ]),
        ]);
    }

    /**
     * Detalle de una acción (se carga por AJAX en el modal).
     */
    public function detalleAccion(Request $request, int $id): Response
    {
        $accion = BitacoraAccion::detalle($id);

        if ($accion === null) {
            return $this->json([
                'success' => false,
                'message' => 'El registro de acción no existe.',
            ], 404);
        }

        return $this->json([
            'success' => true,
            'accion' => [
                'id' => $accion['id'],
                'fecha_hora' => $accion['fecha_hora'],
                'fecha_hora_legible' => $this->fechaLegible($accion['fecha_hora']),
                'nombre_completo' => $accion['nombre_completo'],
                'usuario' => $accion['usuario'],
                'rol' => $accion['rol_nombre'] !== '' ? $accion['rol_nombre'] : '—',
                'rol_clave' => $accion['rol_clave'],
                'permiso_nombre' => $accion['permiso_nombre'] !== '' ? $accion['permiso_nombre'] : '—',
                'permiso_codigo' => $accion['permiso_codigo'],
                'permiso_clave' => $accion['permiso_clave'],
                'tipo_accion' => $accion['tipo_accion'],
                'accion' => $accion['accion'],
                'tabla_afectada' => $accion['tabla_afectada'],
                'registro_afectado' => $accion['registro_afectado'],
                'detalle' => $accion['detalle'],
                'ip_address' => $accion['ip_address'],
                'user_agent' => $accion['user_agent'],
                'resultado' => $accion['resultado'],
                'resultado_etiqueta' => $accion['resultado_etiqueta'],
                'datos_anteriores' => $accion['datos_anteriores'],
                'datos_nuevos' => $accion['datos_nuevos']
            ],
        ]);
    }

    /**
     * Exporta a PDF la página de acciones que se está viendo.
     */
    public function pdfAcciones(Request $request): Response
    {
        $filtros = $this->filtros($request);

        $acciones = BitacoraAccion::listarTodos($filtros);
        $config = ConfiguracionSistema::publica();

        $html = View::render('portal.bitacora.pdf_acciones', [
            'acciones' => $acciones,
            'filtros' => $filtros,
            'total' => count($acciones),
            'hoy' => date('d/m/Y'),
            'hora' => date('H:i'),
            'config' => $config,
        ]);

        return $this->salirPdf($html, $config, 'bitacora-acciones-' . date('Ymd-His') . '.pdf', 'acciones');
    }

    /**
     * Exporta a CSV todas las acciones que coincidan con los filtros,
     * con el detalle de qué se hizo, sobre qué tabla y sus datos.
     *
     * Mismos registros que pdfAcciones(): solo cambia la salida (datos
     * puros separados por ";", sin formato).
     */
    public function csvAcciones(Request $request): Response
    {
        $filtros = $this->filtros($request);
        $acciones = BitacoraAccion::listarTodos($filtros);

        $encabezados = [
            'fecha_hora' => 'Fecha y hora',
            'nombre_completo' => 'Persona',
            'usuario' => 'Usuario',
            'rol_nombre' => 'Rol',
            'permiso_nombre' => 'Permiso',
            'permiso_clave' => 'Clave de permiso',
            'tipo_accion' => 'Tipo de acción',
            'accion' => 'Acción',
            'tabla_afectada' => 'Tabla afectada',
            'registro_afectado' => 'Registro afectado',
            'detalle' => 'Detalle',
            'resultado' => 'Resultado',
            'ip_address' => 'IP',
            'user_agent' => 'Equipo',
            'datos_anteriores' => 'Datos anteriores',
            'datos_nuevos' => 'Datos nuevos',
        ];

        $filas = [];

        foreach ($acciones as $accion) {
            $filas[] = [
                'fecha_hora' => $this->fechaLegible((string) $accion['fecha_hora']),
                'nombre_completo' => (string) $accion['nombre_completo'],
                'usuario' => (string) $accion['usuario'],
                'rol_nombre' => (string) ($accion['rol_nombre'] !== '' ? $accion['rol_nombre'] : '—'),
                'permiso_nombre' => (string) ($accion['permiso_nombre'] !== '' ? $accion['permiso_nombre'] : '—'),
                'permiso_clave' => (string) ($accion['permiso_clave'] !== '' ? $accion['permiso_clave'] : '—'),
                'tipo_accion' => (string) $accion['tipo_accion'],
                'accion' => (string) $accion['accion'],
                'tabla_afectada' => (string) ($accion['tabla_afectada'] !== '' ? $accion['tabla_afectada'] : '—'),
                'registro_afectado' => (string) $accion['registro_afectado'],
                'detalle' => (string) $accion['detalle'],
                'resultado' => (string) $accion['resultado_etiqueta'],
                'ip_address' => (string) $accion['ip_address'],
                'user_agent' => (string) $accion['user_agent'],
                'datos_anteriores' => (string) ($accion['datos_anteriores'] ?? ''),
                'datos_nuevos' => (string) ($accion['datos_nuevos'] ?? ''),
            ];
        }

        return Csv::descargar('bitacora-acciones-' . date('Ymd-His') . '.csv', $encabezados, $filas);
    }

    /**
     * Resultado del acceso: exitoso o fallido.
     */
    private function resultadoAcceso(string $estado): string
    {
        return in_array(strtoupper($estado), ['FALLIDO', 'BLOQUEADO'], true) ? 'FALLIDO' : 'EXITOSO';
    }

    /**
     * Exporta a PDF la página de accesos que se está viendo.
     */
    public function pdf(Request $request): Response
    {
        $filtros = $this->filtros($request);

        $accesos = BitacoraAcceso::listarTodos($filtros);
        $config = ConfiguracionSistema::publica();

        $html = View::render('portal.bitacora.pdf_accesos', [
            'accesos' => $accesos,
            'filtros' => $filtros,
            'total' => count($accesos),
            'hoy' => date('d/m/Y'),
            'hora' => date('H:i'),
            'config' => $config,
        ]);

        return $this->salirPdf($html, $config, 'bitacora-accesos-' . date('Ymd-His') . '.pdf', 'accesos');
    }

    /**
     * Exporta a CSV todos los accesos que coincidan con los filtros.
     *
     * Mismos registros que pdf(): solo cambia la salida (datos puros
     * separados por ";", sin formato).
     */
    public function csv(Request $request): Response
    {
        $filtros = $this->filtros($request);
        $accesos = BitacoraAcceso::listarTodos($filtros);

        $encabezados = [
            'fecha_hora' => 'Fecha y hora',
            'nombre_completo' => 'Persona',
            'usuario_intentado' => 'Usuario',
            'rol_nombre' => 'Rol',
            'estado' => 'Tipo de acceso',
            'resultado' => 'Resultado',
            'detalle' => 'Detalle',
            'ip_address' => 'IP',
            'user_agent' => 'Equipo',
        ];

        $filas = [];

        foreach ($accesos as $acceso) {
            $resultado = $this->resultadoAcceso((string) $acceso['estado']);

            $filas[] = [
                'fecha_hora' => $this->fechaLegible((string) $acceso['fecha_hora']),
                'nombre_completo' => (string) $acceso['nombre_completo'],
                'usuario_intentado' => (string) $acceso['usuario_intentado'],
                'rol_nombre' => (string) ($acceso['rol_nombre'] !== '' ? $acceso['rol_nombre'] : '—'),
                'estado' => BitacoraAcceso::etiquetaEstado((string) $acceso['estado']),
                'resultado' => $resultado === 'EXITOSO' ? 'Exitoso' : 'Fallido',
                'detalle' => (string) $acceso['detalle'],
                'ip_address' => (string) $acceso['ip_address'],
                'user_agent' => (string) $acceso['user_agent'],
            ];
        }

        return Csv::descargar('bitacora-accesos-' . date('Ymd-His') . '.csv', $encabezados, $filas);
    }

    /**
     * Convierte el HTML de una vista en un PDF (dompdf) y lo devuelve al
     * navegador, con el pie de página de cada hoja.
     *
     * @param array<string, mixed> $config
     */
    private function salirPdf(string $html, array $config, string $nombreArchivo, string $tituloPie): Response
    {
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
        $canvas->page_text(
            40,
            812,
            'Bitácora de ' . $tituloPie . ' · ' . (string) ($config['clinica_nombre'] ?? 'Clínica Vida Salud'),
            null,
            7,
            [0.45, 0.45, 0.45]
        );
        $canvas->page_text(430, 812, 'Página {PAGE_NUM} de {PAGE_COUNT}', null, 7, [0.45, 0.45, 0.45]);

        return Response::make($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $nombreArchivo . '"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Normaliza los filtros de búsqueda.
     *
     * @return array{nombre: string, desde: string, hasta: string}
     */
    private function filtros(Request $request): array
    {
        $nombre = trim((string) $request->get('nombre', ''));
        $desde = trim((string) $request->get('desde', ''));
        $hasta = trim((string) $request->get('hasta', ''));

        if ($nombre !== '' && mb_strlen($nombre) > 100) {
            $nombre = mb_substr($nombre, 0, 100);
        }

        if (!$this->fechaValida($desde)) {
            $desde = '';
        }

        if (!$this->fechaValida($hasta)) {
            $hasta = '';
        }

        return ['nombre' => $nombre, 'desde' => $desde, 'hasta' => $hasta];
    }

    /**
     * Formatea la fecha y hora con minutos y segundos.
     */
    private function fechaLegible(string $fechaHora): string
    {
        if ($fechaHora === '') {
            return '—';
        }

        $timestamp = strtotime($fechaHora);

        return $timestamp === false ? $fechaHora : date('d/m/Y H:i:s', $timestamp);
    }

    private function nombre(): string
    {
        return (string) \Lib\Support\Session::get('portal_nombre', 'Usuario');
    }
}
