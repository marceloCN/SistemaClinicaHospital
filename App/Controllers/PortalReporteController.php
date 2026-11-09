<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Especialidad;
use Lib\Core\Database;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\View;

/**
 * Gestor de consultas de los reportes del portal.
 *
 * Centraliza las consultas de reportes del sistema: el módulo de
 * horarios de atención gestiona solo los horarios vigentes y, cuando un
 * horario cumple su fecha de fin, sus fichas pasan a consultarse aquí
 * (reporte de fichas históricas). Cada reporte expone su método de
 * consulta para que el controlador sea el único que sabe cómo se
 * arma cada dato.
 */
class PortalReporteController extends Controller
{
    private const POR_PAGINA = 20;

    /**
     * Panel de reportes: acceso a cada reporte disponible.
     */
    public function index(Request $request): Response
    {
        return $this->view('portal.base.layout', [
            'rolClave' => $this->rolClave(),
            'nombre' => $this->nombre(),
            'active' => 'reportes',
            'content' => View::render('portal.reportes.index', [
                'estadisticas' => self::estadisticasPanel(),
                'rolClave' => $this->rolClave(),
            ]),
        ]);
    }

    /**
     * Reporte de fichas de agenda.
     *
     * Muestra todas las fichas generadas por los horarios de atención,
     * con filtros por rango de fechas, médico, especialidad, estado de la
     * ficha, vigencia del horario y paciente (por la cita asociada, cuando
     * exista).
     */
    public function fichaHistorica(Request $request): Response
    {
        $pagina = max(1, (int) $request->get('page', 1));
        $filtros = $this->filtrosFichaHistorica($request);

        $listado = self::consultarFichasHistoricas($filtros, $pagina, self::POR_PAGINA);
        $estadisticas = self::estadisticasFichasHistoricas($filtros);

        return $this->view('portal.base.layout', [
            'rolClave' => $this->rolClave(),
            'nombre' => $this->nombre(),
            'active' => 'reportes',
            'content' => View::render('portal.reportes.ficha-historica', [
                'fichas' => $listado['items'],
                'estadisticas' => $estadisticas,
                'total' => $listado['total'],
                'pagina' => $listado['pagina'],
                'paginas' => $listado['paginas'],
                'porPagina' => $listado['porPagina'],
                'numerosPagina' => $this->numerosPagina($listado['pagina'], $listado['paginas']),
                'filtros' => $filtros,
                'catalogoEspecialidades' => Especialidad::activas(),
            ]),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Consultas del reporte de fichas históricas                          */
    /* ------------------------------------------------------------------ */

    /**
     * Fichas de agenda paginadas con médico, especialidad, cita y
     * paciente. Incluye fichas de horarios vigentes y vencidos.
     *
     * @param array{desde: string, hasta: string, medico: string, especialidad: string, estado: string, paciente: string, vigencia: string} $filtros
     * @return array{items: array, total: int, pagina: int, paginas: int, porPagina: int}
     */
    private static function consultarFichasHistoricas(array $filtros, int $pagina, int $porPagina): array
    {
        $porPagina = max(1, min(100, $porPagina));
        $pagina = max(1, $pagina);

        [$where, $params] = self::condicionesFichaHistorica($filtros);

        $conteo = Database::raw(
            'SELECT COUNT(DISTINCT fa.id) AS total
             FROM ficha_agenda fa
             JOIN horario_atencion h ON h.id = fa.id_horario
             JOIN servicio_medico sm ON sm.id = h.id_servicio_medico
             JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
             JOIN medico m ON m.id_persona = me.id_medico
             JOIN persona p ON p.id = m.id_persona
             JOIN especialidad e ON e.id = me.id_especialidad
             LEFT JOIN cita c ON c.id_ficha_agenda = fa.id
             LEFT JOIN paciente pa ON pa.id_persona = c.id_paciente
             LEFT JOIN persona pp ON pp.id = pa.id_persona
             WHERE ' . $where,
            $params
        );

        $total = (int) ($conteo[0]['total'] ?? 0);
        $paginas = (int) max(1, ceil($total / $porPagina));
        $pagina = min($pagina, $paginas);
        $offset = ($pagina - 1) * $porPagina;

        $filas = Database::raw(
            'SELECT fa.id,
                    fa.fecha,
                    fa.fecha_hora_inicio,
                    fa.fecha_hora_fin,
                    fa.estado AS estado_ficha,
                    h.fecha_hasta AS horario_hasta,
                    (p.nombre || \' \' || p.apellido) AS nombre_completo,
                    p.ci,
                    m.nro_colegiado,
                    e.nombre AS especialidad,
                    c.codigo AS cita_codigo,
                    c.estado AS cita_estado,
                    (pp.nombre || \' \' || pp.apellido) AS paciente_nombre,
                    pp.ci AS paciente_ci
             FROM ficha_agenda fa
             JOIN horario_atencion h ON h.id = fa.id_horario
             JOIN servicio_medico sm ON sm.id = h.id_servicio_medico
             JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
             JOIN medico m ON m.id_persona = me.id_medico
             JOIN persona p ON p.id = m.id_persona
             JOIN especialidad e ON e.id = me.id_especialidad
             LEFT JOIN cita c ON c.id_ficha_agenda = fa.id
             LEFT JOIN paciente pa ON pa.id_persona = c.id_paciente
             LEFT JOIN persona pp ON pp.id = pa.id_persona
             WHERE ' . $where
            . ' ORDER BY fa.fecha DESC, fa.fecha_hora_inicio DESC, fa.id DESC'
            . ' LIMIT ' . $porPagina . ' OFFSET ' . $offset,
            $params
        );

        $items = [];

        foreach ((array) $filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $items[] = [
                'id' => (int) ($fila['id'] ?? 0),
                'fecha' => (string) ($fila['fecha'] ?? ''),
                'fecha_hora_inicio' => (string) ($fila['fecha_hora_inicio'] ?? ''),
                'fecha_hora_fin' => (string) ($fila['fecha_hora_fin'] ?? ''),
                'estado_ficha' => (string) ($fila['estado_ficha'] ?? ''),
                'horario_hasta' => (string) ($fila['horario_hasta'] ?? ''),
                'nombre_completo' => (string) ($fila['nombre_completo'] ?? ''),
                'ci' => (string) ($fila['ci'] ?? ''),
                'nro_colegiado' => (string) ($fila['nro_colegiado'] ?? ''),
                'especialidad' => (string) ($fila['especialidad'] ?? ''),
                'cita_codigo' => ($fila['cita_codigo'] ?? null) !== null ? (string) $fila['cita_codigo'] : null,
                'cita_estado' => ($fila['cita_estado'] ?? null) !== null ? (string) $fila['cita_estado'] : null,
                'paciente_nombre' => ($fila['paciente_nombre'] ?? null) !== null ? (string) $fila['paciente_nombre'] : null,
                'paciente_ci' => ($fila['paciente_ci'] ?? null) !== null ? (string) $fila['paciente_ci'] : null,
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
            'porPagina' => $porPagina,
        ];
    }

    /**
     * Totales del reporte filtrado: fichas totales, por estado, con cita,
     * sin cita y atendidas (cita en estado ATENDIDA).
     *
     * @param array{desde: string, hasta: string, medico: string, especialidad: string, estado: string, paciente: string, vigencia: string} $filtros
     * @return array{total: int, abiertas: int, cerradas: int, bloqueadas: int, con_cita: int, sin_cita: int, atendidas: int}
     */
    private static function estadisticasFichasHistoricas(array $filtros): array
    {
        $vacias = ['total' => 0, 'abiertas' => 0, 'cerradas' => 0, 'bloqueadas' => 0, 'con_cita' => 0, 'sin_cita' => 0, 'atendidas' => 0];
        [$where, $params] = self::condicionesFichaHistorica($filtros);

        $filas = Database::raw(
            "SELECT COUNT(DISTINCT fa.id) AS total,
                    COUNT(DISTINCT CASE WHEN fa.estado = 'ABIERTA' THEN fa.id END) AS abiertas,
                    COUNT(DISTINCT CASE WHEN fa.estado = 'CERRADA' THEN fa.id END) AS cerradas,
                    COUNT(DISTINCT CASE WHEN fa.estado = 'BLOQUEADA' THEN fa.id END) AS bloqueadas,
                    COUNT(DISTINCT CASE WHEN c.id IS NOT NULL THEN fa.id END) AS con_cita,
                    COUNT(DISTINCT CASE WHEN c.id IS NULL THEN fa.id END) AS sin_cita,
                    COUNT(DISTINCT CASE WHEN c.estado = 'ATENDIDA' THEN c.id END) AS atendidas
             FROM ficha_agenda fa
             JOIN horario_atencion h ON h.id = fa.id_horario
             JOIN servicio_medico sm ON sm.id = h.id_servicio_medico
             JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
             JOIN medico m ON m.id_persona = me.id_medico
             JOIN persona p ON p.id = m.id_persona
             JOIN especialidad e ON e.id = me.id_especialidad
             LEFT JOIN cita c ON c.id_ficha_agenda = fa.id
             LEFT JOIN paciente pa ON pa.id_persona = c.id_paciente
             LEFT JOIN persona pp ON pp.id = pa.id_persona
             WHERE " . $where,
            $params
        );

        if (!is_array($filas[0] ?? null)) {
            return $vacias;
        }

        return [
            'total' => (int) ($filas[0]['total'] ?? 0),
            'abiertas' => (int) ($filas[0]['abiertas'] ?? 0),
            'cerradas' => (int) ($filas[0]['cerradas'] ?? 0),
            'bloqueadas' => (int) ($filas[0]['bloqueadas'] ?? 0),
            'con_cita' => (int) ($filas[0]['con_cita'] ?? 0),
            'sin_cita' => (int) ($filas[0]['sin_cita'] ?? 0),
            'atendidas' => (int) ($filas[0]['atendidas'] ?? 0),
        ];
    }

    /**
     * Resumen del panel de reportes.
     *
     * @return array{fichas_totales: int, horarios_vencidos: int}
     */
    private static function estadisticasPanel(): array
    {
        $filas = Database::raw(
            "SELECT (SELECT COUNT(*) FROM ficha_agenda) AS fichas_totales,
                    (SELECT COUNT(*) FROM horario_atencion
                     WHERE fecha_hasta IS NOT NULL AND fecha_hasta < CURRENT_DATE) AS horarios_vencidos"
        );

        if (!is_array($filas[0] ?? null)) {
            return ['fichas_totales' => 0, 'horarios_vencidos' => 0];
        }

        return [
            'fichas_totales' => (int) ($filas[0]['fichas_totales'] ?? 0),
            'horarios_vencidos' => (int) ($filas[0]['horarios_vencidos'] ?? 0),
        ];
    }

    /**
     * WHERE compartido por el listado y las estadísticas del reporte.
     *
     * Por defecto se incluyen todas las fichas. El filtro opcional
     * `vigencia` permite restringir según la vigencia del horario padre.
     *
     * @param array{desde?: string, hasta?: string, medico?: string, especialidad?: string, estado?: string, paciente?: string, vigencia?: string} $filtros
     * @return array{0: string, 1: array<string, mixed>}
     */
    private static function condicionesFichaHistorica(array $filtros): array
    {
        $donde = ['1=1'];
        $params = [];

        $vigencia = (string) ($filtros['vigencia'] ?? '');

        if ($vigencia === 'vigente') {
            $donde[] = '(h.fecha_hasta IS NULL OR h.fecha_hasta >= CURRENT_DATE)';
        } elseif ($vigencia === 'vencido') {
            $donde[] = '(h.fecha_hasta IS NOT NULL AND h.fecha_hasta < CURRENT_DATE)';
        }

        $desde = trim((string) ($filtros['desde'] ?? ''));

        if ($desde !== '') {
            $donde[] = 'fa.fecha >= :desde';
            $params['desde'] = $desde;
        }

        $hasta = trim((string) ($filtros['hasta'] ?? ''));

        if ($hasta !== '') {
            $donde[] = 'fa.fecha <= :hasta';
            $params['hasta'] = $hasta;
        }

        $medico = trim((string) ($filtros['medico'] ?? ''));

        if ($medico !== '') {
            $donde[] = "((p.nombre || ' ' || p.apellido) ILIKE :medico
                         OR p.ci ILIKE :medico OR m.nro_colegiado ILIKE :medico)";
            $params['medico'] = '%' . $medico . '%';
        }

        $especialidad = (string) ($filtros['especialidad'] ?? '');

        if ($especialidad !== '' && (int) $especialidad > 0) {
            $donde[] = 'e.id = :especialidad';
            $params['especialidad'] = (int) $especialidad;
        }

        $estado = (string) ($filtros['estado'] ?? '');

        if (in_array($estado, ['ABIERTA', 'CERRADA', 'BLOQUEADA'], true)) {
            $donde[] = 'fa.estado = :estado';
            $params['estado'] = $estado;
        }

        $paciente = trim((string) ($filtros['paciente'] ?? ''));

        if ($paciente !== '') {
            $donde[] = "((pp.nombre || ' ' || pp.apellido) ILIKE :paciente OR pp.ci ILIKE :paciente)";
            $params['paciente'] = '%' . $paciente . '%';
        }

        return [implode(' AND ', $donde), $params];
    }

    /**
     * Filtros del reporte ya normalizados.
     *
     * @return array{desde: string, hasta: string, medico: string, especialidad: string, estado: string, paciente: string, vigencia: string}
     */
    private function filtrosFichaHistorica(Request $request): array
    {
        $desde = trim((string) $request->get('desde', ''));
        $hasta = trim((string) $request->get('hasta', ''));

        if (!$this->fechaValida($desde)) {
            $desde = '';
        }

        if (!$this->fechaValida($hasta)) {
            $hasta = '';
        }

        if ($desde !== '' && $hasta !== '' && $hasta < $desde) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $medico = trim((string) $request->get('medico', ''));
        $paciente = trim((string) $request->get('paciente', ''));

        $especialidad = (string) $request->get('especialidad', '');

        if ($especialidad !== '' && (int) $especialidad <= 0) {
            $especialidad = '';
        }

        $estado = (string) $request->get('estado', '');

        if (!in_array($estado, ['ABIERTA', 'CERRADA', 'BLOQUEADA'], true)) {
            $estado = '';
        }

        $vigencia = (string) $request->get('vigencia', '');

        if (!in_array($vigencia, ['vigente', 'vencido'], true)) {
            $vigencia = '';
        }

        return [
            'desde' => $desde !== '' ? mb_substr($desde, 0, 10) : '',
            'hasta' => $hasta !== '' ? mb_substr($hasta, 0, 10) : '',
            'medico' => $medico !== '' ? mb_substr($medico, 0, 100) : '',
            'especialidad' => $especialidad,
            'estado' => $estado,
            'paciente' => $paciente !== '' ? mb_substr($paciente, 0, 100) : '',
            'vigencia' => $vigencia,
        ];
    }

    private function nombre(): string
    {
        return (string) \Lib\Support\Session::get('portal_nombre', 'Usuario');
    }

    /* ------------------------------------------------------------------ */
    /* Reporte: expediente e historial de citas del paciente               */
    /* ------------------------------------------------------------------ */

    /**
     * Reporte de expediente clínico e historial de citas de un paciente.
     *
     * Permite buscar por nombre o CI. Si hay una sola coincidencia muestra
     * el expediente y la línea de tiempo clínica; si hay varias, lista las
     * coincidencias; si no hay, informa elegantemente.
     */
    public function pacienteExpediente(Request $request): Response
    {
        $busqueda = trim((string) $request->get('busqueda', ''));
        $busqueda = mb_substr($busqueda, 0, 100);
        $idParam = (int) $request->get('id', 0);

        $coincidencias = [];
        $paciente = null;

        if ($idParam > 0) {
            $paciente = self::pacientePorId($idParam);
        } elseif ($busqueda !== '') {
            $coincidencias = self::buscarPacientes($busqueda);

            if (count($coincidencias) === 1) {
                $paciente = self::pacientePorId((int) $coincidencias[0]['id']);
                $coincidencias = [];
            }
        }

        $data = [
            'rolClave' => $this->rolClave(),
            'nombre' => $this->nombre(),
            'active' => 'reportes',
            'content' => View::render('portal.reportes.paciente-expediente', [
                'busqueda' => $busqueda,
                'coincidencias' => $coincidencias,
                'paciente' => $paciente,
                'expediente' => null,
                'alergias' => [],
                'antecedentes' => [],
                'medicamentos' => [],
                'citas' => [],
            ]),
        ];

        if ($paciente !== null) {
            $idPaciente = (int) $paciente['id'];
            $expediente = self::expedienteDePaciente($idPaciente);
            $idExpediente = $expediente !== null ? (int) $expediente['id'] : 0;

            $data['content'] = View::render('portal.reportes.paciente-expediente', [
                'busqueda' => $busqueda,
                'coincidencias' => [],
                'paciente' => $paciente,
                'expediente' => $expediente,
                'alergias' => $idExpediente > 0 ? self::alergiasDeExpediente($idExpediente) : [],
                'antecedentes' => $idExpediente > 0 ? self::antecedentesDeExpediente($idExpediente) : [],
                'medicamentos' => $idExpediente > 0 ? self::medicamentosDeExpediente($idExpediente) : [],
                'citas' => self::enriquecerCitasPaciente($idPaciente),
            ]);
        }

        return $this->view('portal.base.layout', $data);
    }

    /**
     * Carga el historial de citas de un paciente con evaluación, consulta,
     * diagnósticos y estudios vinculados.
     */
    private static function enriquecerCitasPaciente(int $idPaciente): array
    {
        $citas = self::citasDePaciente($idPaciente);
        $resultado = [];

        foreach ($citas as $cita) {
            $idCita = (int) ($cita['id_cita'] ?? 0);
            $idConsulta = isset($cita['id_consulta']) ? (int) $cita['id_consulta'] : 0;

            $resultado[] = [
                'cita' => $cita,
                'evaluacion' => $idCita > 0 ? self::evaluacionDeCita($idCita) : null,
                'diagnosticos' => $idConsulta > 0 ? self::diagnosticosDeConsulta($idConsulta) : [],
                'estudios' => $idConsulta > 0 ? self::estudiosDeConsulta($idConsulta) : [],
            ];
        }

        return $resultado;
    }

    /**
     * Busca pacientes por CI, nombre completo, nombre o apellido.
     */
    private static function buscarPacientes(string $termino): array
    {
        if ($termino === '') {
            return [];
        }

        $filas = Database::raw(
            "SELECT p.id, p.ci, p.nombre, p.apellido, p.telefono, p.fecha_nacimiento, p.sexo
             FROM persona p
             JOIN paciente pa ON pa.id_persona = p.id
             WHERE p.ci ILIKE :q
                OR (p.nombre || ' ' || p.apellido) ILIKE :q
                OR p.nombre ILIKE :q
                OR p.apellido ILIKE :q
             ORDER BY p.apellido, p.nombre
             LIMIT 20",
            ['q' => '%' . $termino . '%']
        );

        return self::normalizarFilas($filas);
    }

    /**
     * Datos de un paciente por su id de persona.
     */
    private static function pacientePorId(int $id): ?array
    {
        $filas = Database::raw(
            "SELECT p.id, p.ci, p.nombre, p.apellido, p.telefono, p.correo,
                    p.fecha_nacimiento, p.sexo, p.estado, pa.fecha_alta,
                    pa.observaciones AS observaciones_paciente,
                    (SELECT COUNT(*) FROM cita WHERE id_paciente = p.id) AS total_citas
             FROM persona p
             JOIN paciente pa ON pa.id_persona = p.id
             WHERE p.id = :id",
            ['id' => $id]
        );

        $items = self::normalizarFilas($filas);

        return $items[0] ?? null;
    }

    /**
     * Expediente clínico y perfil médico de un paciente.
     */
    private static function expedienteDePaciente(int $idPaciente): ?array
    {
        $filas = Database::raw(
            "SELECT e.id, e.id_paciente, e.fecha_apertura, e.observaciones_generales, e.estado,
                    pm.grupo_sanguineo, pm.factor_rh, pm.observaciones AS observaciones_perfil,
                    pm.fecha_actualizacion
             FROM expediente_clinico e
             LEFT JOIN perfil_medico pm ON pm.id_expediente = e.id
             WHERE e.id_paciente = :paciente",
            ['paciente' => $idPaciente]
        );

        $items = self::normalizarFilas($filas);

        return $items[0] ?? null;
    }

    private static function alergiasDeExpediente(int $idExpediente): array
    {
        return self::normalizarFilas(Database::raw(
            "SELECT id, sustancia, tipo, reaccion, gravedad, fecha_registro, observaciones, estado
             FROM alergia
             WHERE id_expediente = :id AND estado = TRUE
             ORDER BY gravedad, sustancia",
            ['id' => $idExpediente]
        ));
    }

    private static function antecedentesDeExpediente(int $idExpediente): array
    {
        return self::normalizarFilas(Database::raw(
            "SELECT id, tipo, descripcion, fecha_registro, estado
             FROM antecedente_medico
             WHERE id_expediente = :id AND estado = TRUE
             ORDER BY fecha_registro DESC",
            ['id' => $idExpediente]
        ));
    }

    private static function medicamentosDeExpediente(int $idExpediente): array
    {
        return self::normalizarFilas(Database::raw(
            "SELECT id, nombre_medicamento, dosis, frecuencia, fecha_inicio, fecha_fin,
                    prescrito_por, observaciones, estado
             FROM medicamento_paciente
             WHERE id_expediente = :id AND estado = TRUE
             ORDER BY fecha_inicio DESC NULLS LAST, nombre_medicamento",
            ['id' => $idExpediente]
        ));
    }

    /**
     * Citas del paciente con médico, especialidad y consulta vinculada.
     */
    private static function citasDePaciente(int $idPaciente): array
    {
        return self::normalizarFilas(Database::raw(
            "SELECT c.id AS id_cita,
                    c.codigo,
                    c.estado AS cita_estado,
                    c.motivo_consulta,
                    c.fecha_registro,
                    fa.fecha,
                    fa.fecha_hora_inicio,
                    fa.fecha_hora_fin,
                    pm.id AS id_medico,
                    (pm.nombre || ' ' || pm.apellido) AS medico,
                    e.nombre AS especialidad,
                    cm.id AS id_consulta,
                    cm.estado AS consulta_estado,
                    cm.fecha_inicio,
                    cm.fecha_fin,
                    cm.anamnesis,
                    cm.examen_fisico,
                    cm.evolucion,
                    cm.plan_tratamiento,
                    cm.observaciones AS consulta_observaciones,
                    rc.estado AS recepcion_estado
             FROM cita c
             JOIN ficha_agenda fa ON fa.id = c.id_ficha_agenda
             JOIN horario_atencion h ON h.id = fa.id_horario
             JOIN servicio_medico sm ON sm.id = h.id_servicio_medico
             JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
             JOIN especialidad e ON e.id = me.id_especialidad
             JOIN persona pm ON pm.id = me.id_medico
             LEFT JOIN consulta_medica cm ON cm.id_cita = c.id
             LEFT JOIN recepcion_cita rc ON rc.id_cita = c.id
             WHERE c.id_paciente = :paciente
             ORDER BY fa.fecha_hora_inicio DESC, c.id DESC",
            ['paciente' => $idPaciente]
        ));
    }

    private static function evaluacionDeCita(int $idCita): ?array
    {
        $filas = Database::raw(
            "SELECT id, id_cita, id_personal_registro, fecha_hora, motivo_referido,
                    anamnesis_preliminar, presion_sistolica, presion_diastolica,
                    frecuencia_cardiaca, frecuencia_respiratoria, temperatura,
                    saturacion_oxigeno, peso_kg, talla_cm, observaciones, estado
             FROM evaluacion_previa
             WHERE id_cita = :cita",
            ['cita' => $idCita]
        );

        $items = self::normalizarFilas($filas);

        return $items[0] ?? null;
    }

    private static function consultaDeCita(int $idCita): ?array
    {
        $filas = Database::raw(
            "SELECT id, id_cita, fecha_inicio, fecha_fin, anamnesis, examen_fisico,
                    evolucion, plan_tratamiento, observaciones, estado
             FROM consulta_medica
             WHERE id_cita = :cita",
            ['cita' => $idCita]
        );

        $items = self::normalizarFilas($filas);

        return $items[0] ?? null;
    }

    private static function diagnosticosDeConsulta(int $idConsulta): array
    {
        return self::normalizarFilas(Database::raw(
            "SELECT id, id_consulta, descripcion, tipo, observaciones
             FROM diagnostico
             WHERE id_consulta = :consulta
             ORDER BY CASE tipo WHEN 'PRINCIPAL' THEN 0 ELSE 1 END, id",
            ['consulta' => $idConsulta]
        ));
    }

    private static function estudiosDeConsulta(int $idConsulta): array
    {
        return self::normalizarFilas(Database::raw(
            "SELECT es.id, es.id_consulta, es.id_diagnostico, es.id_tipo_estudio,
                    es.fecha_solicitud, es.indicacion, es.estado, es.fecha_realizacion,
                    es.resultado, es.fecha_resultado, es.observaciones,
                    te.nombre AS tipo_estudio,
                    d.descripcion AS diagnostico
             FROM estudio_solicitado es
             JOIN tipo_estudio te ON te.id = es.id_tipo_estudio
             LEFT JOIN diagnostico d ON d.id = es.id_diagnostico
             WHERE es.id_consulta = :consulta
             ORDER BY es.fecha_solicitud, es.id",
            ['consulta' => $idConsulta]
        ));
    }

    /**
     * Normaliza filas de Database::raw a arrays asociativos limpios.
     */
    private static function normalizarFilas(mixed $filas): array
    {
        return array_filter((array) $filas, 'is_array');
    }

    /**
     * Edad en años a partir de la fecha de nacimiento.
     */
    private static function edadPaciente(?string $fechaNacimiento): ?int
    {
        if ($fechaNacimiento === null || $fechaNacimiento === '') {
            return null;
        }

        $n = \DateTime::createFromFormat('Y-m-d', substr($fechaNacimiento, 0, 10));

        return $n === false ? null : (int) $n->diff(new \DateTime())->y;
    }
}
