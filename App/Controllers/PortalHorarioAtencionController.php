<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Controller;
use App\Models\Especialidad;
use App\Models\FichaAgenda;
use App\Models\HorarioAtencion;
use App\Models\ServicioMedico;
use App\Requests\GenerarFichasRequest;
use App\Requests\HorarioAccionRequest;
use App\Requests\StoreHorarioAtencionRequest;
use App\Requests\UpdateHorarioAtencionRequest;
use Lib\Core\Database;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\View;
use Lib\Support\Csv;

/**
 * Gestor de horarios de atención de médicos.
 *
 * Cada horario define una recurrencia semanal (día, hora inicio/fin y
 * duración de ficha) dentro de una vigencia. Desde un horario se generan
 * las fichas de agenda concretas que los pacientes pueden reservar.
 */
class PortalHorarioAtencionController extends Controller
{
    private const POR_PAGINA = 10;

    /**
     * Dashboard del módulo: estadísticas, filtros y listado paginado.
     */
    public function index(Request $request): Response
    {
        $pagina = max(1, (int) $request->get('page', 1));
        $filtros = $this->filtros($request);
        $listado = HorarioAtencion::listar($filtros, $pagina, self::POR_PAGINA);
        $estadisticas = HorarioAtencion::estadisticas();

        $idsHorarios = array_column($listado['items'], 'id');
        $conteos = HorarioAtencion::conteosPorHorario($idsHorarios);

        return $this->view('portal.base.layout', [
            'rolClave' => $this->rolClave(),
            'nombre' => $this->nombre(),
            'active' => 'horario-atencion',
            'content' => View::render('portal.horario-atencion.index', [
                'estadisticas' => $estadisticas,
                'horarios' => $listado['items'],
                'conteos' => $conteos,
                'total' => $listado['total'],
                'pagina' => $listado['pagina'],
                'paginas' => $listado['paginas'],
                'porPagina' => $listado['porPagina'],
                'numerosPagina' => $this->numerosPagina($listado['pagina'], $listado['paginas']),
                'filtroMedico' => $filtros['medico'],
                'filtroEspecialidad' => $filtros['especialidad'],
                'filtroEstado' => $filtros['estado'],
                'catalogoEspecialidades' => Especialidad::activas(),
                'catalogoServicios' => ServicioMedico::listarTodos(['estado' => 'ACTIVO']),
            ]),
        ]);
    }

    /**
     * Crea un nuevo horario de atención.
     *
     * La validación (CSRF, campos, formatos y rangos) la hace el
     * FormRequest antes de entrar aquí.
     */
    public function store(StoreHorarioAtencionRequest $request): Response
    {
        $datos = $request->datosHorario();

        if (HorarioAtencion::solapado(
            (int) $datos['id_servicio_medico'],
            (int) $datos['dia_semana'],
            (string) $datos['hora_inicio'],
            (string) $datos['hora_fin']
        )) {
            return $this->json([
                'success' => false,
                'message' => 'El horario se solapa con otro horario activo del mismo servicio médico.',
            ], 422);
        }

        $servicio = ServicioMedico::detalle((int) $datos['id_servicio_medico']);

        if ($servicio === null) {
            return $this->json(['success' => false, 'message' => 'El servicio médico no existe.'], 404);
        }

        if ($servicio['estado'] !== 'ACTIVO') {
            return $this->json([
                'success' => false,
                'message' => 'El servicio médico no está activo; no se pueden crear horarios.',
            ], 422);
        }

        try {
            $datos['estado'] = true;
            $idNuevo = (int) HorarioAtencion::query()->insert($datos);

            $this->registrarBitacoraAccion($this->idPersona(), 'horario_atencion', 'INSERT', $idNuevo, [], $datos,
                'Alta de horario de atención para ' . $servicio['nombre_completo'] . ' · ' . $servicio['especialidad']
            );

            return $this->json([
                'success' => true,
                'message' => 'Horario de atención registrado correctamente.',
            ]);
        } catch (\Throwable $e) {
            error_log('Error al guardar horario: ' . $e->getMessage());

            $mensaje = 'No se pudo guardar el horario. Revisa los datos e intenta de nuevo.';

            if (config('app.debug')) {
                $mensaje .= ' [debug] ' . $e->getMessage();
            }

            return $this->json(['success' => false, 'message' => $mensaje]);
        }
    }

    /**
     * Modifica un horario existente.
     *
     * No se permite editar si ya tiene fichas con citas asociadas. Si solo
     * tiene fichas sin citas, solo se puede cambiar la vigencia (el estado
     * se conserva) para no invalidar las fichas ya generadas.
     *
     * La validación (CSRF, campos, formatos y rangos) la hace el
     * FormRequest antes de entrar aquí.
     */
    public function update(UpdateHorarioAtencionRequest $request, int $id): Response
    {
        if ($id <= 0) {
            return $this->json(['success' => false, 'message' => 'Horario no válido.'], 404);
        }

        $actual = HorarioAtencion::detalle($id);

        if ($actual === null) {
            return $this->json(['success' => false, 'message' => 'El horario no existe.'], 404);
        }

        if (HorarioAtencion::tieneFichasGeneradas($id) > 0) {
            return $this->json([
                'success' => false,
                'message' => 'No se puede modificar el horario porque ya tiene fichas asociadas. '
                    . 'Establece una nueva vigencia o crea un horario alternativo.',
            ], 422);
        }

        $datosNuevos = $request->datosHorario();
        $tieneFichas = HorarioAtencion::tieneFichasGeneradas($id);

        if ($tieneFichas) {
            // Con fichas ya generadas (pero sin citas) solo se toca la vigencia.
            // El estado se conserva: el modal ya no envía estado y crear siempre
            // deja el horario activo.
            $datosNuevos = [
                'fecha_desde' => $datosNuevos['fecha_desde'],
                'fecha_hasta' => $datosNuevos['fecha_hasta'],
                'estado' => (bool) $actual['estado'],
            ];
        }

        if (!$tieneFichas && HorarioAtencion::solapado(
            (int) $datosNuevos['id_servicio_medico'],
            (int) $datosNuevos['dia_semana'],
            (string) $datosNuevos['hora_inicio'],
            (string) $datosNuevos['hora_fin'],
            $id
        )) {
            return $this->json([
                'success' => false,
                'message' => 'El horario se solapa con otro horario activo del mismo servicio médico.',
            ], 422);
        }

        try {
            HorarioAtencion::query()->update($datosNuevos, ['id' => $id]);

            $this->registrarBitacoraAccion($this->idPersona(), 'horario_atencion', 'UPDATE', $id,
                [
                    'fecha_desde' => $actual['fecha_desde'],
                    'fecha_hasta' => $actual['fecha_hasta'],
                    'estado' => $actual['estado'],
                ],
                $datosNuevos,
                'Edición de horario de atención de ' . $actual['nombre_completo']
                    . ' · ' . $actual['especialidad']
            );

            return $this->json([
                'success' => true,
                'message' => 'Horario actualizado correctamente.',
            ]);
        } catch (\Throwable $e) {
            error_log('Error al actualizar horario: ' . $e->getMessage());

            $mensaje = 'No se pudo actualizar el horario. Revisa los datos e intenta de nuevo.';

            if (config('app.debug')) {
                $mensaje .= ' [debug] ' . $e->getMessage();
            }

            return $this->json(['success' => false, 'message' => $mensaje], 500);
        }
    }

    /**
     * Alterna el estado del horario: da de baja si está activo y lo
     * reactiva si está inactivo.
     *
     * Solo aplica mientras el horario siga vigente (fecha_hasta >= hoy);
     * uno vencido deja de gestionarse aquí y sus fichas pasan al reporte
     * de fichas históricas. En la baja se mantienen las reglas previas:
     * no puede tener fichas cerradas y se eliminan las fichas libres. En
     * la reactivación se comprueba que no se solape con otro horario
     * activo del mismo servicio médico.
     *
     * El FormRequest solo valida método y CSRF; la existencia, la
     * vigencia y las citas asociadas se comprueban aquí.
     */
    public function destroy(HorarioAccionRequest $request, int $id): Response
    {
        if ($id <= 0) {
            return $this->json(['success' => false, 'message' => 'Horario no válido.']);
        }

        $actual = HorarioAtencion::detalle($id);

        if ($actual === null) {
            return $this->json(['success' => false, 'message' => 'El horario no existe.']);
        }

        $vigente = (string) $actual['fecha_hasta'] === ''
            || (string) $actual['fecha_hasta'] >= date('Y-m-d');

        if (!$vigente) {
            return $this->json([
                'success' => false,
                'message' => 'El horario ya no está vigente; sus fichas se consultan en el reporte de fichas históricas.',
            ]);
        }

        // Reactivar un horario inactivo: no debe solaparse con otro activo.
        if (!(bool) $actual['estado']) {
            if (HorarioAtencion::solapado((int) $actual['id_servicio_medico'],(int) $actual['dia_semana'],(string) $actual['hora_inicio'],(string) $actual['hora_fin'],$id)) {
                return $this->json([
                    'success' => false,
                    'message' => 'No se puede reactivar porque el horario se solapa con otro horario activo del mismo servicio médico.',
                ]);
            }

            try {
                HorarioAtencion::query()->update(['estado' => true], ['id' => $id]);

                $this->registrarBitacoraAccion($this->idPersona(), 'horario_atencion', 'UPDATE', $id,
                    ['estado' => $actual['estado']],
                    ['estado' => true],
                    'Reactivación de horario de atención de ' . $actual['nombre_completo']
                        . ' · ' . $actual['especialidad']
                );

                return $this->json([
                    'success' => true,
                    'message' => 'Horario reactivado correctamente.',
                ]);
            } catch (\Throwable $e) {
                error_log('Error al reactivar horario: ' . $e->getMessage());

                return $this->json([
                    'success' => false,
                    'message' => 'No se pudo reactivar el horario.',
                ], 500);
            }
        }

        //si tenes fichas con estado CERRADO me entregas la cantidad porque sabre que la cita esta programada o atendida
        if (HorarioAtencion::tieneFichasConCitas($id) > 0) {
            $conCitas = HorarioAtencion::tieneFichasConCitas($id);

            return $this->json([
                'success' => false,
                'message' => 'No se puede desactivar porque tiene ' . $conCitas . ' ficha' . ($conCitas === 1 ? '' : 's') . ' con citas asociadas.',
            ]);
        }

        //si tenia hichas y estas estan en estado ABIERTA, tambien se puede proceder a eliminar porque no se han generado citas
        if (HorarioAtencion::tieneFichasGeneradas($id) > 0){
            try {
                $afectadas = (int) Database::raw(
                    "DELETE FROM ficha_agenda
                    WHERE id_horario = :id_horario and estado = 'ABIERTA'",
                    ['id_horario' => $id]
                );
                $this->registrarBitacoraAccion($this->idPersona(), 'ficha_agenda', 'DELETE', $id, [],
                    [
                    'id_horario' => $id,
                    'fichas_eliminadas' => $afectadas,
                    ],
                'Eliminación de ' . $afectadas . ' ficha(s) restante(s) del horario de atención'
                );
            } catch (\Throwable $e) {
                error_log('Error al eliminar fichas restantes: ' . $e->getMessage());
                $mensaje = 'No se pudieron eliminar las fichas restantes.';
                if (config('app.debug')) {
                    $mensaje .= ' [debug] ' . $e->getMessage();
                }
                return $this->json(['success' => false, 'message' => $mensaje], 500);    
            }
        }

        // dar de baja el horario de atencion si es un update
        try {
            HorarioAtencion::query()->update(['estado' => false], ['id' => $id]);

            $this->registrarBitacoraAccion($this->idPersona(),'horario_atencion','UPDATE',$id,
                ['estado' => $actual['estado']],
                ['estado' => false],
                'Desactivación de horario de atención de ' . $actual['nombre_completo']
                    . ' · ' . $actual['especialidad']
            );

            return $this->json([
                'success' => true,
                'message' => 'Horario desactivado correctamente.',
            ]);
        } catch (\Throwable $e) {
            error_log('Error al desactivar horario: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => 'No se pudo desactivar el horario.',
            ], 500);
        }
    }

    /**
     * Genera fichas de agenda para un rango de fechas a partir de un horario.
     *
     * El FormRequest valida el CSRF y el rango (fechas válidas, orden y
     * máximo 90 días); aquí solo se comprueban el horario y su vigencia.
     */
    public function generarFichas(GenerarFichasRequest $request, int $id): Response
    {
        if ($id <= 0) {
            error_log('generarFichas: ID de horario no válido (' . $id . ')');

            return $this->json(['success' => false, 'message' => 'Horario no válido.'], 404);
        }

        $horario = HorarioAtencion::query()->find($id);

        if ($horario === null) {
            error_log('generarFichas: horario no encontrado (' . $id . ')');

            return $this->json(['success' => false, 'message' => 'El horario no existe.'], 404);
        }

        if (!(bool) ($horario['estado'] ?? false)) {
            error_log('generarFichas: horario inactivo (' . $id . ')');

            return $this->json([
                'success' => false,
                'message' => 'No se pueden generar fichas en un horario inactivo.',
            ], 422);
        }

        $rangos = $request->datosFichas();
        $fechaInicio = $rangos['fecha_inicio'];
        $fechaFin = $rangos['fecha_fin'];

        $diaSemana = (int) ($horario['dia_semana'] ?? 0);
        $horaInicio = (string) ($horario['hora_inicio'] ?? '');
        $horaFin = (string) ($horario['hora_fin'] ?? '');
        $duracion = (int) ($horario['duracion_ficha'] ?? 0);
        $fechaDesde = (string) ($horario['fecha_desde'] ?? '');
        $fechaHasta = (string) ($horario['fecha_hasta'] ?? '');

        if ($diaSemana < 1 || $diaSemana > 7 || $duracion <= 0 || $horaInicio === '' || $horaFin === '') {
            error_log('generarFichas: datos del horario incompletos (' . $id . ')');

            return $this->json([
                'success' => false,
                'message' => 'El horario no tiene datos válidos para generar fichas.',
            ], 422);
        }

        $limiteInicio = max($fechaInicio, $fechaDesde);
        $limiteFin = $fechaHasta !== '' ? min($fechaFin, $fechaHasta) : $fechaFin;

        if ($limiteFin < $limiteInicio) {
            error_log('generarFichas: rango fuera de vigencia (' . $limiteInicio . ' / ' . $limiteFin . ')');

            return $this->json([
                'success' => false,
                'message' => 'El rango indicado queda fuera de la vigencia del horario.',
            ], 422);
        }

        try {
            $generadas = self::crearFichasEnRango($id,$diaSemana,$horaInicio,$horaFin,$duracion,$limiteInicio,$limiteFin);

            $this->registrarBitacoraAccion($this->idPersona(),'ficha_agenda','INSERT',$id,
                [],
                [
                    'id_horario' => $id,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'generadas' => $generadas,
                ],
                'Generación de ' . $generadas . ' ficha(s) de agenda desde horario de atención'
            );

            return $this->json([
                'success' => true,
                'message' => 'Se generaron ' . $generadas . ' ficha(s) de agenda.',
                'generadas' => $generadas,
            ]);
        } catch (\Throwable $e) {
            error_log('Error al generar fichas: ' . $e->getMessage());

            $mensaje = 'No se pudieron generar las fichas. Revisa los datos e intenta de nuevo.';

            if (config('app.debug')) {
                $mensaje .= ' [debug] ' . $e->getMessage();
            }

            return $this->json(['success' => false, 'message' => $mensaje], 500);
        }
    }

    /**
     * Elimina las fichas de agenda de un horario que no tengan citas asociadas.
     *
     * El FormRequest solo valida método y CSRF; la existencia del horario
     * se comprueba aquí.
     */
    public function eliminarFichasRestantes(HorarioAccionRequest $request, int $id): Response
    {
        if ($id <= 0) {
            error_log('eliminarFichasRestantes: ID de horario no válido (' . $id . ')');

            return $this->json(['success' => false, 'message' => 'Horario no válido.'], 404);
        }

        $horario = HorarioAtencion::query()->find($id);

        if ($horario === null) {
            error_log('eliminarFichasRestantes: horario no encontrado (' . $id . ')');

            return $this->json(['success' => false, 'message' => 'El horario no existe.'], 404);
        }

        try {
            $afectadas = (int) Database::raw(
                "DELETE FROM ficha_agenda
                 WHERE id_horario = :id_horario and estado = 'ABIERTA'",
                ['id_horario' => $id]
            );

            $this->registrarBitacoraAccion($this->idPersona(), 'ficha_agenda', 'DELETE', $id, [],
                [
                    'id_horario' => $id,
                    'fichas_eliminadas' => $afectadas,
                ],
                'Eliminación de ' . $afectadas . ' ficha(s) restante(s) del horario de atención'
            );

            return $this->json([
                'success' => true,
                'message' => 'Se eliminaron ' . $afectadas . ' ficha' . ($afectadas === 1 ? '' : 's') . ' restante' . ($afectadas === 1 ? '' : 's') . '.',
                'eliminadas' => $afectadas,
            ]);
        } catch (\Throwable $e) {
            error_log('Error al eliminar fichas restantes: ' . $e->getMessage());

            $mensaje = 'No se pudieron eliminar las fichas restantes.';

            if (config('app.debug')) {
                $mensaje .= ' [debug] ' . $e->getMessage();
            }

            return $this->json(['success' => false, 'message' => $mensaje], 500);
        }
    }

    /**
     * Reporte PDF con los filtros aplicados (médico, especialidad, estado).
     */
    public function pdf(Request $request): Response
    {
        $filtros = $this->filtros($request);
        $registros = HorarioAtencion::listarTodos($filtros);
        $conteos = HorarioAtencion::conteosPorHorario(array_column($registros, 'id'));

        $nombreEspecialidad = '';

        if ($filtros['especialidad'] !== '') {
            $detalleEspecialidad = Especialidad::detalle((int) $filtros['especialidad']);
            $nombreEspecialidad = (string) ($detalleEspecialidad['nombre']
                ?? ('#' . $filtros['especialidad']));
        }

        $config = \App\Models\ConfiguracionSistema::publica();

        $html = View::render('portal.horario-atencion.pdf', [
            'registros' => $registros,
            'filtros' => $filtros,
            'nombreEspecialidad' => $nombreEspecialidad,
            'conteos' => $conteos,
            'total' => count($registros),
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
        $canvas->page_text(40, 812, 'Reporte de horarios de atención'
            . ' · ' . (string) ($config['clinica_nombre'] ?? 'Clínica Vida Salud'), null, 7, [0.45, 0.45, 0.45]);
        $canvas->page_text(430, 812, 'Página {PAGE_NUM} de {PAGE_COUNT}', null, 7, [0.45, 0.45, 0.45]);

        return Response::make($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="horario-atencion-' . date('Ymd-His') . '.pdf"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Reporte CSV con los mismos datos y filtros del PDF.
     *
     * Mismos registros que pdf(): solo cambia la salida (datos puros
     * separados por ";", sin formato).
     */
    public function csv(Request $request): Response
    {
        $filtros = $this->filtros($request);
        $registros = HorarioAtencion::listarTodos($filtros);
        $conteos = HorarioAtencion::conteosPorHorario(array_column($registros, 'id'));

        $encabezados = [
            'nombre_completo' => 'Médico',
            'ci' => 'CI',
            'nro_colegiado' => 'Nro. colegiado',
            'especialidad' => 'Especialidad',
            'dia_nombre' => 'Día',
            'hora_inicio' => 'Hora inicio',
            'hora_fin' => 'Hora fin',
            'duracion_ficha' => 'Duración ficha (min)',
            'fecha_desde' => 'Vigente desde',
            'fecha_hasta' => 'Vigente hasta',
            'estado' => 'Estado',
            'total_fichas' => 'Fichas generadas',
            'libres' => 'Fichas libres',
            'cerradas' => 'Fichas cerradas',
        ];

        $filas = [];

        foreach ($registros as $registro) {
            $conteo = $conteos[(int) $registro['id']]
                ?? ['total_fichas' => 0, 'libres' => 0, 'cerradas' => 0];

            $filas[] = [
                'nombre_completo' => (string) $registro['nombre_completo'],
                'ci' => (string) $registro['ci'],
                'nro_colegiado' => (string) $registro['nro_colegiado'],
                'especialidad' => (string) $registro['especialidad'],
                'dia_nombre' => (string) $registro['dia_nombre'],
                'hora_inicio' => (string) $registro['hora_inicio'],
                'hora_fin' => (string) $registro['hora_fin'],
                'duracion_ficha' => (int) $registro['duracion_ficha'],
                'fecha_desde' => (string) $registro['fecha_desde'],
                'fecha_hasta' => (string) $registro['fecha_hasta'],
                'estado' => ((bool) $registro['estado']) ? 'Activo' : 'Inactivo',
                'total_fichas' => (int) $conteo['total_fichas'],
                'libres' => (int) $conteo['libres'],
                'cerradas' => (int) $conteo['cerradas'],
            ];
        }

        return Csv::descargar('horario-atencion-' . date('Ymd-His') . '.csv', $encabezados, $filas);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Filtros del listado de horarios.
     *
     * @return array{medico: string, especialidad: string, estado: string}
     */
    private function filtros(Request $request): array
    {
        $medico = trim((string) $request->get('medico', ''));
        $especialidad = trim((string) $request->get('especialidad', ''));
        $estado = trim((string) $request->get('estado', ''));

        if ($medico !== '') {
            $medico = mb_substr($medico, 0, 100);
        }

        if ($especialidad !== '' && (int) $especialidad <= 0) {
            $especialidad = '';
        }

        if (!in_array($estado, ['1', '0'], true)) {
            $estado = '';
        }

        return [
            'medico' => $medico,
            'especialidad' => $especialidad,
            'estado' => $estado,
        ];
    }

    /**
     * Crea las fichas de agenda dentro del rango indicado, respetando el día
     * de la semana del horario y evitando duplicados.
     */
    private static function crearFichasEnRango(
        int $idHorario,
        int $diaSemana,
        string $horaInicio,
        string $horaFin,
        int $duracion,
        string $limiteInicio,
        string $limiteFin
    ): int {
        $inicio = new \DateTime($limiteInicio . ' 00:00:00');
        $fin = new \DateTime($limiteFin . ' 23:59:59');
        $generadas = 0;

        Database::transaction(static function () use (
            $idHorario,
            $diaSemana,
            $horaInicio,
            $horaFin,
            $duracion,
            $inicio,
            $fin,
            &$generadas
        ) {
            $intervalo = new \DateInterval('P1D');
            $periodo = new \DatePeriod($inicio, $intervalo, (clone $fin)->modify('+1 second'));

            foreach ($periodo as $fecha) {
                if ((int) $fecha->format('N') !== $diaSemana) {
                    continue;
                }

                $fechaStr = $fecha->format('Y-m-d');
                $slotInicio = new \DateTime($fechaStr . ' ' . $horaInicio);
                $slotFin = clone $slotInicio;
                $slotFin->add(new \DateInterval('PT' . $duracion . 'M'));
                $finJornada = new \DateTime($fechaStr . ' ' . $horaFin);

                while ($slotInicio < $finJornada && $slotFin <= $finJornada) {
                    $existe = Database::raw(
                        'SELECT COUNT(*) AS total FROM ficha_agenda
                         WHERE id_horario = :id_horario
                           AND fecha = :fecha
                           AND fecha_hora_inicio = :inicio',
                        [
                            'id_horario' => $idHorario,
                            'fecha' => $fechaStr,
                            'inicio' => $slotInicio->format('Y-m-d H:i:s'),
                        ]
                    );

                    if ((int) ($existe[0]['total'] ?? 0) === 0) {
                        FichaAgenda::query()->insert([
                            'id_horario' => $idHorario,
                            'fecha' => $fechaStr,
                            'fecha_hora_inicio' => $slotInicio->format('Y-m-d H:i:s'),
                            'fecha_hora_fin' => $slotFin->format('Y-m-d H:i:s'),
                            'capacidad' => 1,
                            'estado' => 'ABIERTA',
                        ]);

                        $generadas++;
                    }

                    $slotInicio = clone $slotFin;
                    $slotFin = clone $slotInicio;
                    $slotFin->add(new \DateInterval('PT' . $duracion . 'M'));
                }
            }
        });

        return $generadas;
    }

    private function nombre(): string
    {
        return (string) \Lib\Support\Session::get('portal_nombre', 'Usuario');
    }
}
