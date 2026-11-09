<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Alergia;
use App\Models\AntecedenteMedico;
use App\Models\Cita;
use App\Models\ConsultaMedica;
use App\Models\Diagnostico;
use App\Models\EvaluacionPrevia;
use App\Models\ExpedienteClinico;
use App\Models\EstudioSolicitado;
use App\Models\MedicamentoPaciente;
use App\Models\PerfilMedico;
use App\Models\RecepcionCita;
use App\Requests\AccionesConsultaRequest;
use App\Requests\CrearExpedienteRequest;
use App\Requests\DiagnosticoRequest;
use App\Requests\EliminarRegistroRequest;
use App\Requests\EstudioRequest;
use App\Requests\GuardarAlergiaRequest;
use App\Requests\GuardarAntecedenteRequest;
use App\Requests\GuardarConsultaRequest;
use App\Requests\GuardarEvaluacionRequest;
use App\Requests\GuardarMedicamentoRequest;
use App\Requests\GuardarPerfilRequest;
use App\Requests\IniciarEvaluacionRequest;
use App\Requests\SinEvaluacionRequest;
use Lib\Core\Database;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\View;

/**
 * Flujo integral de recepción, evaluación previa, consulta médica,
 * expediente clínico e historial del paciente.
 *
 * Estados respetados (CHECK de la base de datos):
 *
 * | Etapa                | cita.estado  | recepcion_cita.estado   | evaluacion_previa.estado | consulta_medica.estado |
 * |----------------------|--------------|-------------------------|--------------------------|------------------------|
 * | Llegada              | EN_RECEPCION | EN_ESPERA               | -                        | -                      |
 * | Inicio evaluación    | EN_RECEPCION | EN_EVALUACION           | EN_CURSO                 | -                      |
 * | Evaluación lista     | EN_RECEPCION | LISTO_PARA_CONSULTA     | COMPLETADA               | -                      |
 * | Sin evaluación       | EN_RECEPCION | LISTO_PARA_CONSULTA     | ANULADA / sin registro   | -                      |
 * | Consulta iniciada    | EN_RECEPCION | LISTO_PARA_CONSULTA     | COMPLETADA/ANULADA/nula  | EN_CURSO               |
 * | Consulta finalizada  | ATENDIDA     | FINALIZADO              | COMPLETADA/ANULADA/nula  | FINALIZADA             |
 *
 * Nota: la especificación menciona COMPLETADA para consulta_medica,
 * pero el CHECK de la BD solo permite EN_CURSO/FINALIZADA; se usa
 * FINALIZADA para no inventar estados ni alterar el esquema.
 */
class PortalAtencionController extends Controller
{
    private const POR_PAGINA = 10;

    /** Estados de recepción válidos como filtro de la bandeja. */
    private const ESTADOS_RECEPCION = ['EN_ESPERA', 'EN_EVALUACION', 'LISTO_PARA_CONSULTA', 'FINALIZADO'];

    /* ------------------------------------------------------------------ */
    /* BANDEJA PRINCIPAL                                                    */
    /* ------------------------------------------------------------------ */

    /**
     * Panel del flujo de atención: la recepción/administración gestiona
     * la espera y la evaluación previa; el médico ve su bandeja de
     * pacientes listos para consulta y sus consultas en curso.
     */
    public function index(Request $request): Response
    {
        $rol = $this->rolClave();

        if ($rol === 'RECEP' || $rol === 'ADMIN') {
            return $this->bandejaRecepcion($request);
        }

        if ($rol === 'MEDICO') {
            return $this->bandejaMedica($request, true);
        }

        return Response::redirect(route('portal_dashboard') ?? '/portal/dashboard');
    }

    /** Bandeja de recepción: pacientes llegados pendientes del flujo. */
    private function bandejaRecepcion(Request $request): Response
    {
        $pagina = max(1, (int) $request->get('page', 1));
        $filtroEstado = trim((string) $request->get('estado', ''));
        $filtroTexto = trim((string) $request->get('texto', ''));

        if (!in_array($filtroEstado, self::ESTADOS_RECEPCION, true)) {
            $filtroEstado = '';
        }

        if ($filtroTexto !== '') {
            $filtroTexto = mb_substr($filtroTexto, 0, 100);
        }

        $listado = self::listarFlujo('recepcion', $filtroEstado, $filtroTexto, $pagina, self::POR_PAGINA, null);

        return $this->view('portal.base.layout', [
            'rolClave' => $this->rolClave(),
            'nombre' => $this->nombre(),
            'active' => 'atencion',
            'content' => View::render('portal.atencion.index', [
                'vista' => 'recepcion',
                'pacientes' => $listado['items'],
                'total' => $listado['total'],
                'pagina' => $listado['pagina'],
                'paginas' => $listado['paginas'],
                'porPagina' => $listado['porPagina'],
                'numerosPagina' => $this->numerosPagina($listado['pagina'], $listado['paginas']),
                'filtroEstado' => $filtroEstado,
                'filtroTexto' => $filtroTexto,
                'estadisticas' => self::estadisticasRecepcion(),
            ]),
        ]);
    }

    /** Bandeja médica: pacientes listos + consultas en curso del médico. */
    private function bandejaMedica(Request $request, bool $esMedico): Response
    {
        $pagina = max(1, (int) $request->get('page', 1));
        $filtroTexto = trim((string) $request->get('texto', ''));

        if ($filtroTexto !== '') {
            $filtroTexto = mb_substr($filtroTexto, 0, 100);
        }

        $idMedico = $esMedico ? $this->idPersona() : null;
        $listado = self::listarFlujo('medico', '', $filtroTexto, $pagina, self::POR_PAGINA, $idMedico);

        return $this->view('portal.base.layout', [
            'rolClave' => $this->rolClave(),
            'nombre' => $this->nombre(),
            'active' => 'atencion',
            'content' => View::render('portal.atencion.index', [
                'vista' => 'medico',
                'pacientes' => $listado['items'],
                'total' => $listado['total'],
                'pagina' => $listado['pagina'],
                'paginas' => $listado['paginas'],
                'porPagina' => $listado['porPagina'],
                'numerosPagina' => $this->numerosPagina($listado['pagina'], $listado['paginas']),
                'filtroEstado' => '',
                'filtroTexto' => $filtroTexto,
                'estadisticas' => self::estadisticasMedico($idMedico),
            ]),
        ]);
    }

    /**
     * Listado paginado del flujo de atención.
     *
     * @param 'recepcion'|'medico' $vista
     * @param int|null $idMedico Restringe al médico logueado (bandeja médica).
     * @return array{items: array, total: int, pagina: int, paginas: int, porPagina: int}
     */
    private static function listarFlujo(
        string $vista,
        string $filtroEstado,
        string $filtroTexto,
        int $pagina,
        int $porPagina,
        ?int $idMedico
    ): array {
        $donde = [];
        $params = [];

        if ($vista === 'recepcion') {
            $donde[] = "c.estado = 'EN_RECEPCION'";

            if ($filtroEstado !== '') {
                $donde[] = 'rc.estado = :estado';
                $params['estado'] = $filtroEstado;
            }
        } else {
            // Bandeja médica: pacientes habilitados y consultas propias en curso.
            $donde[] = "c.estado = 'EN_RECEPCION'";
            $donde[] = "(rc.estado = 'LISTO_PARA_CONSULTA' OR cm.estado = 'EN_CURSO')";

            if ($idMedico !== null) {
                $donde[] = 'me.id_medico = :medico';
                $params['medico'] = $idMedico;
            }
        }

        if ($filtroTexto !== '') {
            $donde[] = "((p.nombre || ' ' || p.apellido) ILIKE :texto OR p.ci ILIKE :texto OR c.codigo ILIKE :texto)";
            $params['texto'] = '%' . $filtroTexto . '%';
        }

        $where = implode(' AND ', $donde);

        $from = 'FROM recepcion_cita rc
                 JOIN cita c ON c.id = rc.id_cita
                 JOIN ficha_agenda fa ON fa.id = c.id_ficha_agenda
                 JOIN horario_atencion h ON h.id = fa.id_horario
                 JOIN servicio_medico sm ON sm.id = h.id_servicio_medico
                 JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
                 JOIN especialidad e ON e.id = me.id_especialidad
                 JOIN persona pm ON pm.id = me.id_medico
                 JOIN paciente pac ON pac.id_persona = c.id_paciente
                 JOIN persona p ON p.id = pac.id_persona
                 LEFT JOIN evaluacion_previa ev ON ev.id_cita = c.id
                 LEFT JOIN consulta_medica cm ON cm.id_cita = c.id';

        $total = 0;
        $conteo = Database::raw("SELECT COUNT(*) AS total {$from} WHERE {$where}", $params);

        if (is_array($conteo) && isset($conteo[0]['total'])) {
            $total = (int) $conteo[0]['total'];
        }

        $paginas = (int) max(1, ceil($total / $porPagina));
        $pagina = min($pagina, $paginas);
        $offset = ($pagina - 1) * $porPagina;

        $filas = Database::raw(
            "SELECT c.id,
                    c.id_paciente,
                    c.codigo,
                    c.estado AS cita_estado,
                    c.motivo_consulta,
                    fa.fecha_hora_inicio,
                    fa.fecha_hora_fin,
                    (p.nombre || ' ' || p.apellido) AS paciente,
                    p.ci AS paciente_ci,
                    (pm.nombre || ' ' || pm.apellido) AS medico,
                    e.nombre AS especialidad,
                    rc.id AS id_recepcion,
                    rc.estado AS recepcion_estado,
                    rc.fecha_hora_llegada,
                    ev.id AS id_evaluacion,
                    ev.estado AS evaluacion_estado,
                    cm.id AS id_consulta,
                    cm.estado AS consulta_estado
             {$from}
             WHERE {$where}
             ORDER BY fa.fecha_hora_inicio, c.id
             LIMIT {$porPagina} OFFSET {$offset}",
            $params
        );

        $items = [];

        foreach ((array) $filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $items[] = [
                'id' => (int) ($fila['id'] ?? 0),
                'id_paciente' => (int) ($fila['id_paciente'] ?? 0),
                'codigo' => (string) ($fila['codigo'] ?? ''),
                'cita_estado' => (string) ($fila['cita_estado'] ?? ''),
                'motivo_consulta' => (string) ($fila['motivo_consulta'] ?? ''),
                'fecha_hora_inicio' => (string) ($fila['fecha_hora_inicio'] ?? ''),
                'fecha_hora_fin' => (string) ($fila['fecha_hora_fin'] ?? ''),
                'paciente' => (string) ($fila['paciente'] ?? ''),
                'paciente_ci' => (string) ($fila['paciente_ci'] ?? ''),
                'medico' => (string) ($fila['medico'] ?? ''),
                'especialidad' => (string) ($fila['especialidad'] ?? ''),
                'id_recepcion' => (int) ($fila['id_recepcion'] ?? 0),
                'recepcion_estado' => (string) ($fila['recepcion_estado'] ?? ''),
                'fecha_hora_llegada' => (string) ($fila['fecha_hora_llegada'] ?? ''),
                'id_evaluacion' => isset($fila['id_evaluacion']) && $fila['id_evaluacion'] !== null ? (int) $fila['id_evaluacion'] : null,
                'evaluacion_estado' => (string) ($fila['evaluacion_estado'] ?? ''),
                'id_consulta' => isset($fila['id_consulta']) && $fila['id_consulta'] !== null ? (int) $fila['id_consulta'] : null,
                'consulta_estado' => (string) ($fila['consulta_estado'] ?? ''),
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

    /** Contadores de la tarjeta superior de la bandeja de recepción. */
    private static function estadisticasRecepcion(): array
    {
        $estadisticas = [
            'espera' => 0,
            'evaluacion' => 0,
            'listos' => 0,
            'finalizados' => 0,
        ];

        $filas = Database::raw(
            "SELECT COUNT(*) FILTER (WHERE c.estado = 'EN_RECEPCION' AND rc.estado = 'EN_ESPERA') AS espera,
                    COUNT(*) FILTER (WHERE c.estado = 'EN_RECEPCION' AND rc.estado = 'EN_EVALUACION') AS evaluacion,
                    COUNT(*) FILTER (WHERE c.estado = 'EN_RECEPCION' AND rc.estado = 'LISTO_PARA_CONSULTA') AS listos,
                    COUNT(*) FILTER (WHERE rc.estado = 'FINALIZADO'
                                      AND rc.fecha_hora_llegada >= CURRENT_DATE
                                      AND rc.fecha_hora_llegada < CURRENT_DATE + INTERVAL '1 day') AS finalizados
             FROM recepcion_cita rc
             JOIN cita c ON c.id = rc.id_cita"
        );

        if (is_array($filas) && isset($filas[0]) && is_array($filas[0])) {
            foreach (array_keys($estadisticas) as $clave) {
                $estadisticas[$clave] = (int) ($filas[0][$clave] ?? 0);
            }
        }

        return $estadisticas;
    }

    /** Contadores de la tarjeta superior de la bandeja médica. */
    private static function estadisticasMedico(?int $idMedico): array
    {
        $estadisticas = [
            'listos' => 0,
            'encurso' => 0,
            'atendidas' => 0,
        ];

        $params = [];
        $filtroMedico = '';

        if ($idMedico !== null) {
            $filtroMedico = ' AND me.id_medico = :medico';
            $params['medico'] = $idMedico;
        }

        $filas = Database::raw(
            "SELECT COUNT(*) FILTER (WHERE c.estado = 'EN_RECEPCION' AND rc.estado = 'LISTO_PARA_CONSULTA') AS listos,
                    COUNT(*) FILTER (WHERE cm.estado = 'EN_CURSO') AS encursos,
                    COUNT(*) FILTER (WHERE cm.estado = 'FINALIZADA'
                                      AND cm.fecha_fin >= CURRENT_DATE
                                      AND cm.fecha_fin < CURRENT_DATE + INTERVAL '1 day') AS atendidas
             FROM recepcion_cita rc
             JOIN cita c ON c.id = rc.id_cita
             JOIN ficha_agenda fa ON fa.id = c.id_ficha_agenda
             JOIN horario_atencion h ON h.id = fa.id_horario
             JOIN servicio_medico sm ON sm.id = h.id_servicio_medico
             JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
             LEFT JOIN consulta_medica cm ON cm.id_cita = c.id
             WHERE 1 = 1 {$filtroMedico}",
            $params
        );

        if (is_array($filas) && isset($filas[0]) && is_array($filas[0])) {
            $estadisticas['listos'] = (int) ($filas[0]['listos'] ?? 0);
            $estadisticas['encurso'] = (int) ($filas[0]['encursos'] ?? 0);
            $estadisticas['atendidas'] = (int) ($filas[0]['atendidas'] ?? 0);
        }

        return $estadisticas;
    }

    /* ------------------------------------------------------------------ */
    /* DETALLE DE LA CITA (JSON para modales)                              */
    /* ------------------------------------------------------------------ */

    /**
     * Detalle completo de una cita dentro del flujo: recepción,
     * evaluación previa, consulta, diagnósticos, estudios y estado
     * del expediente clínico del paciente.
     */
    public function detalleCita(int $id): Response
    {
        if (($denegado = $this->autorizarFlujo(true)) !== null) {
            return $denegado;
        }

        if ($id <= 0) {
            return $this->json(['success' => false, 'message' => 'Cita no válida.'], 404);
        }

        $cita = Cita::detalle($id);

        if ($cita === null) {
            return $this->json(['success' => false, 'message' => 'La cita no existe.'], 404);
        }

        $recepcion = Cita::recepcionDe($id);
        $evaluacion = self::evaluacionDe($id);
        $consulta = self::consultaDe($id);

        $diagnosticos = [];
        $estudios = [];

        if ($consulta !== null) {
            $diagnosticos = self::diagnosticosDe((int) $consulta['id']);
            $estudios = self::estudiosDe((int) $consulta['id']);
        }

        $expediente = self::expedienteDe((int) $cita['id_paciente']);

        return $this->json([
            'success' => true,
            'cita' => $cita,
            'recepcion' => $recepcion,
            'evaluacion' => $evaluacion,
            'consulta' => $consulta,
            'diagnosticos' => $diagnosticos,
            'estudios' => $estudios,
            'expediente' => $expediente === null ? null : [
                'id' => (int) $expediente['id'],
                'id_paciente' => (int) $expediente['id_paciente'],
            ],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* FASE 2: EVALUACIÓN PREVIA                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Inicio de la evaluación previa:
     * recepcion_cita EN_EVALUACION + evaluacion_previa EN_CURSO.
     * La cita permanece en EN_RECEPCION.
     */
    public function iniciarEvaluacion(IniciarEvaluacionRequest $request): Response
    {
        if (($denegado = $this->autorizarRecepcion(true)) !== null) {
            return $denegado;
        }

        $idCita = $request->datosEvaluacion()['id_cita'];

        try {
            Database::transaction(function () use ($idCita): void {
                $cita = self::bloquearCita($idCita);

                if ($cita === null) {
                    throw new \RuntimeException('La cita no existe.');
                }

                if ((string) $cita['estado'] !== 'EN_RECEPCION') {
                    throw new \RuntimeException('La cita no está en recepción (estado ' . $cita['estado'] . ').');
                }

                $recepcion = self::bloquearRecepcion($idCita);

                if ($recepcion === null) {
                    throw new \RuntimeException('La cita aún no fue recepcionada.');
                }

                if ((string) $recepcion['estado'] !== 'EN_ESPERA') {
                    throw new \RuntimeException('La evaluación ya fue iniciada o el paciente ya está listo para consulta.');
                }

                $existente = Database::raw(
                    'SELECT id, estado FROM evaluacion_previa WHERE id_cita = :cita',
                    ['cita' => $idCita]
                );

                if (is_array($existente) && $existente !== []) {
                    throw new \RuntimeException('Ya existe una evaluación previa para esta cita.');
                }

                EvaluacionPrevia::query()->insert([
                    'id_cita' => $idCita,
                    'id_personal_registro' => $this->idPersona(),
                    'estado' => 'EN_CURSO',
                ]);

                RecepcionCita::query()->update(['estado' => 'EN_EVALUACION'], ['id' => (int) $recepcion['id']]);
            });
        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->errorInterno('iniciar la evaluación previa', $e);
        }

        $this->registrarBitacoraAccion(
            $this->idPersona(),
            'evaluacion_previa',
            'INSERT',
            $idCita,
            [],
            ['id_cita' => $idCita, 'estado' => 'EN_CURSO'],
            'Inicio de evaluación previa'
        );

        return $this->json([
            'success' => true,
            'message' => 'Evaluación previa iniciada. Completa los datos para habilitar al paciente.',
        ]);
    }

    /**
     * Finalización de la evaluación previa:
     * evaluacion_previa COMPLETADA + recepcion_cita LISTO_PARA_CONSULTA.
     * La cita permanece en EN_RECEPCION.
     */
    public function guardarEvaluacion(GuardarEvaluacionRequest $request): Response
    {
        $datos = $request->datosEvaluacion();
        $idCita = $datos['id_cita'];

        try {
            Database::transaction(function () use ($idCita, $datos): void {
                $cita = self::bloquearCita($idCita);

                if ($cita === null) {
                    throw new \RuntimeException('La cita no existe.');
                }

                if ((string) $cita['estado'] !== 'EN_RECEPCION') {
                    throw new \RuntimeException('La cita no está en recepción (estado ' . $cita['estado'] . ').');
                }

                $recepcion = self::bloquearRecepcion($idCita);

                if ($recepcion === null) {
                    throw new \RuntimeException('La cita aún no fue recepcionada.');
                }

                if ((string) $recepcion['estado'] !== 'EN_EVALUACION') {
                    throw new \RuntimeException('La evaluación previa no está en curso.');
                }

                $existente = Database::raw(
                    'SELECT id, estado FROM evaluacion_previa WHERE id_cita = :cita',
                    ['cita' => $idCita]
                );

                $idEvaluacion = 0;

                if (is_array($existente) && $existente !== []) {
                    $idEvaluacion = (int) $existente[0]['id'];

                    if ((string) $existente[0]['estado'] !== 'EN_CURSO') {
                        throw new \RuntimeException('La evaluación previa ya fue cerrada (estado ' . $existente[0]['estado'] . ').');
                    }
                } else {
                    // Registro tardío: se crea directamente completada.
                    $idEvaluacion = (int) EvaluacionPrevia::query()->insert([
                        'id_cita' => $idCita,
                        'id_personal_registro' => $this->idPersona(),
                        'estado' => 'COMPLETADA',
                    ]);
                }

                $campos = array_filter([
                    'motivo_referido' => $datos['motivo_referido'],
                    'anamnesis_preliminar' => $datos['anamnesis_preliminar'],
                    'presion_sistolica' => $datos['presion_sistolica'],
                    'presion_diastolica' => $datos['presion_diastolica'],
                    'frecuencia_cardiaca' => $datos['frecuencia_cardiaca'],
                    'frecuencia_respiratoria' => $datos['frecuencia_respiratoria'],
                    'temperatura' => $datos['temperatura'],
                    'saturacion_oxigeno' => $datos['saturacion_oxigeno'],
                    'peso_kg' => $datos['peso_kg'],
                    'talla_cm' => $datos['talla_cm'],
                    'observaciones' => $datos['observaciones'],
                ], static fn (mixed $valor): bool => $valor !== null);

                $campos['estado'] = 'COMPLETADA';

                EvaluacionPrevia::query()->update($campos, ['id' => $idEvaluacion]);
                RecepcionCita::query()->update(['estado' => 'LISTO_PARA_CONSULTA'], ['id' => (int) $recepcion['id']]);
            });
        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->errorInterno('guardar la evaluación previa', $e);
        }

        $this->registrarBitacoraAccion(
            $this->idPersona(),
            'evaluacion_previa',
            'UPDATE',
            $idCita,
            [],
            ['id_cita' => $idCita, 'estado' => 'COMPLETADA', 'recepcion' => 'LISTO_PARA_CONSULTA'],
            'Finalización de evaluación previa'
        );

        return $this->json([
            'success' => true,
            'message' => 'Evaluación finalizada. El paciente está listo para consulta.',
        ]);
    }

    /**
     * Paciente sin evaluación previa:
     * recepcion_cita LISTO_PARA_CONSULTA y, si existe un registro de
     * evaluación previa, queda ANULADA con el motivo en observaciones.
     */
    public function sinEvaluacion(SinEvaluacionRequest $request): Response
    {
        $datos = $request->datosDecision();
        $idCita = $datos['id_cita'];

        try {
            Database::transaction(function () use ($idCita, $datos): void {
                $cita = self::bloquearCita($idCita);

                if ($cita === null) {
                    throw new \RuntimeException('La cita no existe.');
                }

                if ((string) $cita['estado'] !== 'EN_RECEPCION') {
                    throw new \RuntimeException('La cita no está en recepción (estado ' . $cita['estado'] . ').');
                }

                $recepcion = self::bloquearRecepcion($idCita);

                if ($recepcion === null) {
                    throw new \RuntimeException('La cita aún no fue recepcionada.');
                }

                $estadoRecepcion = (string) $recepcion['estado'];

                if (!in_array($estadoRecepcion, ['EN_ESPERA', 'EN_EVALUACION'], true)) {
                    throw new \RuntimeException('La recepción ya habilitó al paciente para consulta.');
                }

                $existente = Database::raw(
                    'SELECT id, estado, observaciones FROM evaluacion_previa WHERE id_cita = :cita',
                    ['cita' => $idCita]
                );

                if (is_array($existente) && $existente !== []) {
                    $idEvaluacion = (int) $existente[0]['id'];
                    $estadoEvaluacion = (string) $existente[0]['estado'];

                    if ($estadoEvaluacion === 'EN_CURSO') {
                        $anterior = trim((string) ($existente[0]['observaciones'] ?? ''));
                        $constancia = 'Evaluación anulada: ' . $datos['motivo'];

                        EvaluacionPrevia::query()->update([
                            'estado' => 'ANULADA',
                            'observaciones' => $anterior !== ''
                                ? $anterior . ' | ' . $constancia
                                : $constancia,
                        ], ['id' => $idEvaluacion]);
                    } elseif ($estadoEvaluacion === 'ANULADA') {
                        throw new \RuntimeException('La evaluación previa ya fue anulada.');
                    } else {
                        throw new \RuntimeException('La evaluación previa ya está COMPLETADA; no se puede anular.');
                    }
                }

                RecepcionCita::query()->update([
                    'estado' => 'LISTO_PARA_CONSULTA',
                    'observaciones' => 'Sin evaluación previa: ' . $datos['motivo'],
                ], ['id' => (int) $recepcion['id']]);
            });
        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->errorInterno('registrar la decisión de no evaluación', $e);
        }

        $this->registrarBitacoraAccion(
            $this->idPersona(),
            'recepcion_cita',
            'UPDATE',
            $idCita,
            [],
            ['id_cita' => $idCita, 'recepcion' => 'LISTO_PARA_CONSULTA', 'motivo' => $datos['motivo']],
            'Paciente habilitado para consulta sin evaluación previa'
        );

        return $this->json([
            'success' => true,
            'message' => 'Paciente habilitado para consulta sin evaluación previa.',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* FASE 3: CONSULTA MÉDICA                                              */
    /* ------------------------------------------------------------------ */

    /**
     * Página de la consulta médica de una cita: información de la cita,
     * recepción, evaluación previa, formulario clínico, diagnósticos,
     * estudios solicitados y acceso al expediente/historial.
     */
    public function consulta(int $id): Response
    {
        if ($id <= 0) {
            return Response::redirect(route('dashboard_atencion') ?? '/portal/dashboard/atencion');
        }

        if (($denegado = $this->autorizarMedico(false)) !== null) {
            return $denegado;
        }

        $cita = Cita::detalle($id);

        if ($cita === null) {
            return $this->view('portal.base.layout', [
                'rolClave' => $this->rolClave(),
                'nombre' => $this->nombre(),
                'active' => 'atencion',
                'content' => View::render('portal.atencion.mensaje', [
                    'titulo' => 'Cita no encontrada',
                    'mensaje' => 'La cita solicitada no existe.',
                    'urlVolver' => route('dashboard_atencion') ?? '/portal/dashboard/atencion',
                ]),
            ]);
        }

        $recepcion = Cita::recepcionDe($id);
        $consulta = self::consultaDe($id);
        $evaluacion = self::evaluacionDe($id);

        if ($recepcion === null || (string) $recepcion['estado'] !== 'LISTO_PARA_CONSULTA') {
            if ($consulta === null) {
                return $this->view('portal.base.layout', [
                    'rolClave' => $this->rolClave(),
                    'nombre' => $this->nombre(),
                    'active' => 'atencion',
                    'content' => View::render('portal.atencion.mensaje', [
                        'titulo' => 'Paciente no habilitado',
                        'mensaje' => 'El paciente aún no está en estado LISTO_PARA_CONSULTA para iniciar la atención.',
                        'urlVolver' => route('dashboard_atencion') ?? '/portal/dashboard/atencion',
                    ]),
                ]);
            }
        }

        if ($this->rolClave() !== 'ADMIN' && (int) $cita['id_medico'] !== $this->idPersona()) {
            return $this->view('portal.base.layout', [
                'rolClave' => $this->rolClave(),
                'nombre' => $this->nombre(),
                'active' => 'atencion',
                'content' => View::render('portal.atencion.mensaje', [
                    'titulo' => 'Consulta de otro médico',
                    'mensaje' => 'Esta cita pertenece a otro profesional de la salud.',
                    'urlVolver' => route('dashboard_atencion') ?? '/portal/dashboard/atencion',
                ]),
            ]);
        }

        $diagnosticos = [];
        $estudios = [];

        if ($consulta !== null) {
            $diagnosticos = self::diagnosticosDe((int) $consulta['id']);
            $estudios = self::estudiosDe((int) $consulta['id']);
        }

        $expediente = self::expedienteDe((int) $cita['id_paciente']);
        $tiposEstudio = self::tiposEstudio();

        return $this->view('portal.base.layout', [
            'rolClave' => $this->rolClave(),
            'nombre' => $this->nombre(),
            'active' => 'atencion',
            'content' => View::render('portal.atencion.consulta', [
                'cita' => $cita,
                'recepcion' => $recepcion,
                'evaluacion' => $evaluacion,
                'consulta' => $consulta,
                'diagnosticos' => $diagnosticos,
                'estudios' => $estudios,
                'expediente' => $expediente,
                'tiposEstudio' => $tiposEstudio,
                'urlHistorial' => route('portal_atencion_historial', ['id' => (int) $cita['id_paciente']]) ?? '',
                'urlExpediente' => route('portal_atencion_expediente', ['id' => (int) $cita['id_paciente']]) ?? '',
            ]),
        ]);
    }

    /**
     * Apertura de la consulta médica: crea (si no existe) el registro
     * en consulta_medica con estado EN_CURSO. Idempotente gracias al
     * UNIQUE(id_cita) + ON CONFLICT, y con candado para que dos médicos
     * no abran la misma consulta a la vez.
     */
    public function abrirConsulta(AccionesConsultaRequest $request): Response
    {
        if (($denegado = $this->autorizarMedico(true)) !== null) {
            return $denegado;
        }

        $idCita = $request->datosAccion()['id_cita'];

        try {
            $resultado = Database::transaction(function () use ($idCita): array {
                $cita = self::bloquearCita($idCita);

                if ($cita === null) {
                    throw new \RuntimeException('La cita no existe.');
                }

                if ((string) $cita['estado'] !== 'EN_RECEPCION') {
                    throw new \RuntimeException('La cita ya no está en recepción (estado ' . $cita['estado'] . ').');
                }

                $this->exigirPropietario((int) $cita['id_medico']);

                $recepcion = self::bloquearRecepcion($idCita);

                if ($recepcion === null) {
                    throw new \RuntimeException('La cita no fue recepcionada.');
                }

                if ((string) $recepcion['estado'] !== 'LISTO_PARA_CONSULTA') {
                    throw new \RuntimeException('El paciente no está habilitado para consulta (estado ' . $recepcion['estado'] . ').');
                }

                $consulta = self::asegurarConsulta($idCita);

                return [
                    'id_consulta' => (int) $consulta['id'],
                    'estado' => (string) $consulta['estado'],
                ];
            });
        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->errorInterno('abrir la consulta médica', $e);
        }

        $this->registrarBitacoraAccion($this->idPersona(),'consulta_medica','INSERT',$resultado['id_consulta'],[],
            ['id_cita' => $idCita, 'estado' => $resultado['estado']],'Apertura de consulta médica');

        return $this->json([
            'success' => true,
            'message' => 'Consulta médica iniciada.',
            'id_consulta' => $resultado['id_consulta'],
            'url' => route('portal_atencion_consulta', ['id' => $idCita]) ?? ('/portal/dashboard/atencion/consulta/' . $idCita),
        ]);
    }

    /**
     * Guardado de la información clínica de la consulta en curso
     * (borrador o datos definitivos antes de finalizar).
     */
    public function guardarConsulta(GuardarConsultaRequest $request): Response
    {
        $datos = $request->datosConsulta();

        try {
            Database::transaction(function () use ($datos): void {
                $contexto = self::bloquearParaEditarConsulta($datos['id_cita'], $this->idPersona(), $this->rolClave());

                ConsultaMedica::query()->update([
                    'anamnesis' => $datos['anamnesis'],
                    'examen_fisico' => $datos['examen_fisico'],
                    'evolucion' => $datos['evolucion'],
                    'plan_tratamiento' => $datos['plan_tratamiento'],
                    'observaciones' => $datos['observaciones'],
                ], ['id' => (int) $contexto['consulta']['id']]);
            });
        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->errorInterno('guardar la consulta médica', $e);
        }

        $this->registrarBitacoraAccion($this->idPersona(),'consulta_medica','UPDATE',(int) ($datos['id_cita']),
            [],['id_cita' => $datos['id_cita']],'Guardado de información clínica de la consulta');

        return $this->json([
            'success' => true,
            'message' => 'Información de la consulta guardada.',
        ]);
    }

    /** Registro de un diagnóstico (principal o secundario) de la consulta. */
    public function registrarDiagnostico(DiagnosticoRequest $request): Response
    {
        if (($denegado = $this->autorizarMedico(true)) !== null) {
            return $denegado;
        }

        $datos = $request->datosDiagnostico();

        try {
            $idDiagnostico = (int) Database::transaction(function () use ($datos): int {
                $contexto = self::bloquearParaEditarConsulta($datos['id_cita'], $this->idPersona(), $this->rolClave());

                return (int) Diagnostico::query()->insert([
                    'id_consulta' => (int) $contexto['consulta']['id'],
                    'descripcion' => $datos['descripcion'],
                    'tipo' => $datos['tipo'],
                    'observaciones' => $datos['observaciones'],
                ]);
            });
        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->errorInterno('registrar el diagnóstico', $e);
        }

        $this->registrarBitacoraAccion($this->idPersona(),'diagnostico','INSERT',$idDiagnostico,[],['id_cita' => $datos['id_cita'], 'tipo' => $datos['tipo']],'Registro de diagnóstico en consulta médica');

        return $this->json([
            'success' => true,
            'message' => 'Diagnóstico registrado.',
            'id' => $idDiagnostico,
        ]);
    }

    /** Elimina un diagnóstico de una consulta que sigue en curso. */
    public function eliminarDiagnostico(EliminarRegistroRequest $request): Response
    {
        $id = $request->datosRegistro()['id'];

        try {
            Database::transaction(function () use ($id): void {
                $filas = Database::raw(
                    'SELECT d.id, cm.id AS id_consulta, cm.estado AS consulta_estado, c.id AS id_cita, me.id_medico
                     FROM diagnostico d
                     JOIN consulta_medica cm ON cm.id = d.id_consulta
                     JOIN cita c ON c.id = cm.id_cita
                     JOIN ficha_agenda fa ON fa.id = c.id_ficha_agenda
                     JOIN horario_atencion h ON h.id = fa.id_horario
                     JOIN servicio_medico sm ON sm.id = h.id_servicio_medico
                     JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
                     WHERE d.id = :id
                     FOR UPDATE OF d',
                    ['id' => $id]
                );

                if (!is_array($filas) || $filas === []) {
                    throw new \RuntimeException('El diagnóstico no existe.');
                }

                $this->exigirPropietario((int) $filas[0]['id_medico']);

                if ((string) $filas[0]['consulta_estado'] !== 'EN_CURSO') {
                    throw new \RuntimeException('No se puede modificar una consulta ya finalizada.');
                }

                $estudiosVinculados = Database::raw(
                    'SELECT COUNT(*) AS total FROM estudio_solicitado WHERE id_diagnostico = :id AND id_consulta = :id_consulta',
                    ['id' => $id, 'id_consulta' => (int) $filas[0]['id_consulta']]
                );

                if (is_array($estudiosVinculados) && (int) ($estudiosVinculados[0]['total'] ?? 0) > 0) {
                    throw new \RuntimeException('No se puede eliminar este diagnóstico porque ya tiene estudios solicitados vinculados. Si deseas eliminarlo, primero retira los estudios asociados.');
                }

                Diagnostico::query()->delete(['id' => $id]);
            });
        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->errorInterno('eliminar el diagnóstico', $e);
        }

        $this->registrarBitacoraAccion($this->idPersona(),'diagnostico','DELETE',$id,[],null,'Eliminación de diagnóstico de la consulta médica');

        return $this->json([
            'success' => true,
            'message' => 'Diagnóstico eliminado.',
        ]);
    }

    /** Solicitud de un estudio complementario de la consulta. */
    public function registrarEstudio(EstudioRequest $request): Response
    {
        if (($denegado = $this->autorizarMedico(true)) !== null) {
            return $denegado;
        }

        $datos = $request->datosEstudio();

        try {
            $idEstudio = (int) Database::transaction(function () use ($datos): int {
                $contexto = self::bloquearParaEditarConsulta($datos['id_cita'], $this->idPersona(), $this->rolClave());

                $tipo = Database::raw(
                    'SELECT id, nombre FROM tipo_estudio WHERE id = :id AND estado = TRUE',
                    ['id' => $datos['id_tipo_estudio']]
                );

                if (!is_array($tipo) || $tipo === []) {
                    throw new \RuntimeException('El tipo de estudio seleccionado no existe o está inactivo.');
                }

                if ($datos['id_diagnostico'] !== null) {
                    $pertenece = Database::raw(
                        'SELECT id FROM diagnostico WHERE id = :id AND id_consulta = :consulta',
                        [
                            'id' => $datos['id_diagnostico'],
                            'consulta' => (int) $contexto['consulta']['id'],
                        ]
                    );

                    if (!is_array($pertenece) || $pertenece === []) {
                        throw new \RuntimeException('El diagnóstico seleccionado no pertenece a esta consulta.');
                    }
                }

                return (int) EstudioSolicitado::query()->insert([
                    'id_consulta' => (int) $contexto['consulta']['id'],
                    'id_diagnostico' => $datos['id_diagnostico'],
                    'id_tipo_estudio' => $datos['id_tipo_estudio'],
                    'indicacion' => $datos['indicacion'],
                    'estado' => 'SOLICITADO',
                    'observaciones' => $datos['observaciones'],
                ]);
            });
        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->errorInterno('solicitar el estudio', $e);
        }

        $this->registrarBitacoraAccion(
            $this->idPersona(),
            'estudio_solicitado',
            'INSERT',
            $idEstudio,
            [],
            ['id_cita' => $datos['id_cita'], 'id_tipo_estudio' => $datos['id_tipo_estudio']],
            'Solicitud de estudio complementario'
        );

        return $this->json([
            'success' => true,
            'message' => 'Estudio solicitado.',
            'id' => $idEstudio,
        ]);
    }

    /** Anula un estudio solicitado mientras la consulta siga en curso. */
    public function eliminarEstudio(EliminarRegistroRequest $request): Response
    {
        if (($denegado = $this->autorizarMedico(true)) !== null) {
            return $denegado;
        }

        $id = $request->datosRegistro()['id'];

        try {
            Database::transaction(function () use ($id): void {
                $filas = Database::raw(
                    'SELECT es.id, es.estado, cm.estado AS consulta_estado, me.id_medico
                     FROM estudio_solicitado es
                     JOIN consulta_medica cm ON cm.id = es.id_consulta
                     JOIN cita c ON c.id = cm.id_cita
                     JOIN ficha_agenda fa ON fa.id = c.id_ficha_agenda
                     JOIN horario_atencion h ON h.id = fa.id_horario
                     JOIN servicio_medico sm ON sm.id = h.id_servicio_medico
                     JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
                     WHERE es.id = :id
                     FOR UPDATE OF es',
                    ['id' => $id]
                );

                if (!is_array($filas) || $filas === []) {
                    throw new \RuntimeException('El estudio no existe.');
                }

                $this->exigirPropietario((int) $filas[0]['id_medico']);

                if ((string) $filas[0]['consulta_estado'] !== 'EN_CURSO') {
                    throw new \RuntimeException('No se puede modificar una consulta ya finalizada.');
                }

                if ((string) $filas[0]['estado'] !== 'SOLICITADO') {
                    throw new \RuntimeException('Solo se puede retirar un estudio en estado SOLICITADO (actual: ' . $filas[0]['estado'] . ').');
                }

                EstudioSolicitado::query()->delete(['id' => $id]);
            });
        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->errorInterno('retirar el estudio', $e);
        }

        $this->registrarBitacoraAccion($this->idPersona(),'estudio_solicitado','DELETE',$id,[],
            null,
            'Retiro de estudio solicitado'
        );

        return $this->json([
            'success' => true,
            'message' => 'Estudio retirado.',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* FASE 4: FINALIZACIÓN DE LA CONSULTA                                  */
    /* ------------------------------------------------------------------ */

    /**
     * Finalización transaccional de la consulta:
     * consulta_medica FINALIZADA (fecha_fin) + cita ATENDIDA +
     * recepcion_cita FINALIZADO. Exige anamnesis, examen físico, plan
     * de tratamiento y al menos un diagnóstico principal.
     */
    public function finalizarConsulta(AccionesConsultaRequest $request): Response
    {
        $idCita = $request->datosAccion()['id_cita'];

        try {
            Database::transaction(function () use ($idCita): void {
                $cita = self::bloquearCita($idCita);

                if ($cita === null) {
                    throw new \RuntimeException('La cita no existe.');
                }

                if ((string) $cita['estado'] !== 'EN_RECEPCION') {
                    throw new \RuntimeException('La cita ya no está en recepción (estado ' . $cita['estado'] . ').');
                }

                $this->exigirPropietario((int) $cita['id_medico']);

                $recepcion = self::bloquearRecepcion($idCita);

                if ($recepcion === null || (string) $recepcion['estado'] !== 'LISTO_PARA_CONSULTA') {
                    throw new \RuntimeException('La recepción del paciente no está en estado LISTO_PARA_CONSULTA.');
                }

                $consulta = self::consultaDe($idCita);

                if ($consulta === null) {
                    throw new \RuntimeException('La consulta médica no fue iniciada.');
                }

                if ((string) $consulta['estado'] !== 'EN_CURSO') {
                    throw new \RuntimeException('La consulta ya fue finalizada.');
                }

                $idConsulta = (int) $consulta['id'];

                $faltantes = [];

                if (trim((string) $consulta['anamnesis']) === '') {
                    $faltantes[] = 'anamnesis';
                }

                if (trim((string) $consulta['examen_fisico']) === '') {
                    $faltantes[] = 'examen físico';
                }

                if (trim((string) $consulta['plan_tratamiento']) === '') {
                    $faltantes[] = 'plan de tratamiento';
                }

                if ($faltantes !== []) {
                    throw new \RuntimeException(
                        'Antes de finalizar debes completar: ' . implode(', ', $faltantes) . '.'
                    );
                }

                $principales = Database::raw(
                    "SELECT COUNT(*) AS total FROM diagnostico
                     WHERE id_consulta = :consulta AND tipo = 'PRINCIPAL'",
                    ['consulta' => $idConsulta]
                );

                if ((int) ($principales[0]['total'] ?? 0) === 0) {
                    throw new \RuntimeException('Registra al menos un diagnóstico PRINCIPAL antes de finalizar.');
                }

                ConsultaMedica::query()->update([
                    'estado' => 'FINALIZADA',
                    'fecha_fin' => date('Y-m-d H:i:s'),
                ], ['id' => $idConsulta]);

                Cita::query()->update(['estado' => 'ATENDIDA'], ['id' => $idCita]);
                RecepcionCita::query()->update(['estado' => 'FINALIZADO'], ['id' => (int) $recepcion['id']]);
            });
        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->errorInterno('finalizar la consulta médica', $e);
        }

        $this->registrarBitacoraAccion(
            $this->idPersona(),
            'consulta_medica',
            'UPDATE',
            $idCita,
            ['estado' => 'EN_CURSO'],
            ['consulta' => 'FINALIZADA', 'cita' => 'ATENDIDA', 'recepcion' => 'FINALIZADO'],
            'Finalización de consulta médica'
        );

        return $this->json([
            'success' => true,
            'message' => 'Consulta finalizada. La cita queda ATENDIDA.',
            'url' => route('dashboard_atencion') ?? '/portal/dashboard/atencion',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* FASE 3/5: EXPEDIENTE CLÍNICO                                         */
    /* ------------------------------------------------------------------ */

    /**
     * Página del expediente clínico general del paciente: perfil
     * médico, alergias, antecedentes y medicamentos.
     */
    public function expediente(int $id): Response
    {

        $datos = self::cargarExpedientePaciente($id);

        if ($datos === null) {
            return $this->view('portal.base.layout', [
                'rolClave' => $this->rolClave(),
                'nombre' => $this->nombre(),
                'active' => 'atencion',
                'content' => View::render('portal.atencion.mensaje', [
                    'titulo' => 'Paciente no encontrado',
                    'mensaje' => 'No existe un paciente con ese identificador.',
                    'urlVolver' => route('dashboard_atencion') ?? '/portal/dashboard/atencion',
                ]),
            ]);
        }

        return $this->view('portal.base.layout', [
            'rolClave' => $this->rolClave(),
            'nombre' => $this->nombre(),
            'active' => 'atencion',
            'content' => View::render('portal.atencion.expediente', $datos),
        ]);
    }

    /** Crea el expediente clínico del paciente si aún no existe. */
    public function crearExpediente(CrearExpedienteRequest $request): Response
    {
        if (($denegado = $this->autorizarMedico(true)) !== null) {
            return $denegado;
        }

        $datos = $request->datosExpediente();

        try {
            $resultado = Database::transaction(function () use ($datos): array {
                $paciente = Database::raw(
                    'SELECT id_persona FROM paciente WHERE id_persona = :id FOR UPDATE',
                    ['id' => $datos['id_paciente']]
                );

                if (!is_array($paciente) || $paciente === []) {
                    throw new \RuntimeException('El paciente no existe.');
                }

                $existente = Database::raw(
                    'SELECT id FROM expediente_clinico WHERE id_paciente = :paciente',
                    ['paciente' => $datos['id_paciente']]
                );

                if (is_array($existente) && $existente !== []) {
                    throw new \RuntimeException('El paciente ya tiene un expediente clínico.');
                }

                $id = (int) ExpedienteClinico::query()->insert([
                    'id_paciente' => $datos['id_paciente'],
                    'observaciones_generales' => $datos['observaciones_generales'],
                    'estado' => true,
                ]);

                return ['id' => $id];
            });
        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->errorInterno('crear el expediente clínico', $e);
        }

        $this->registrarBitacoraAccion(
            $this->idPersona(),
            'expediente_clinico',
            'INSERT',
            $resultado['id'],
            [],
            ['id_paciente' => $datos['id_paciente']],
            'Creación de expediente clínico'
        );

        return $this->json([
            'success' => true,
            'message' => 'Expediente clínico creado.',
            'id' => $resultado['id'],
        ]);
    }

    /** Registro/actualización del perfil médico (upsert por expediente). */
    public function guardarPerfil(GuardarPerfilRequest $request): Response
    {
        if (($denegado = $this->autorizarMedico(true)) !== null) {
            return $denegado;
        }

        $datos = $request->datosPerfil();

        try {
            Database::transaction(function () use ($datos): void {
                self::asegurarExpediente((int) $datos['id_expediente']);

                Database::raw(
                    'INSERT INTO perfil_medico (id_expediente, grupo_sanguineo, factor_rh, observaciones, fecha_actualizacion)
                     VALUES (:expediente, :grupo, :rh, :observaciones, CURRENT_TIMESTAMP)
                     ON CONFLICT (id_expediente) DO UPDATE
                     SET grupo_sanguineo = EXCLUDED.grupo_sanguineo,
                         factor_rh = EXCLUDED.factor_rh,
                         observaciones = EXCLUDED.observaciones,
                         fecha_actualizacion = CURRENT_TIMESTAMP',
                    [
                        'expediente' => $datos['id_expediente'],
                        'grupo' => $datos['grupo_sanguineo'],
                        'rh' => $datos['factor_rh'],
                        'observaciones' => $datos['observaciones'],
                    ]
                );
            });
        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->errorInterno('guardar el perfil médico', $e);
        }

        $this->registrarBitacoraAccion($this->idPersona(),'perfil_medico','UPDATE',
            (int) $datos['id_expediente'],[],
            ['grupo_sanguineo' => $datos['grupo_sanguineo'], 'factor_rh' => $datos['factor_rh']],
            'Guardado de perfil médico del expediente'
        );

        return $this->json([
            'success' => true,
            'message' => 'Perfil médico guardado.',
        ]);
    }

    /** Registro de una alergia del paciente en su expediente. */
    public function guardarAlergia(GuardarAlergiaRequest $request): Response
    {
        if (($denegado = $this->autorizarMedico(true)) !== null) {
            return $denegado;
        }

        $datos = $request->datosAlergia();

        try {
            $id = (int) Database::transaction(function () use ($datos): int {
                self::asegurarExpediente((int) $datos['id_expediente']);

                return (int) Alergia::query()->insert($datos);
            });
        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->errorInterno('registrar la alergia', $e);
        }

        $this->registrarBitacoraAccion(
            $this->idPersona(),
            'alergia',
            'INSERT',
            $id,
            [],
            ['id_expediente' => $datos['id_expediente'], 'sustancia' => $datos['sustancia']],
            'Registro de alergia en expediente clínico'
        );

        return $this->json([
            'success' => true,
            'message' => 'Alergia registrada.',
            'id' => $id,
        ]);
    }

    /** Registro de un antecedente médico del paciente. */
    public function guardarAntecedente(GuardarAntecedenteRequest $request): Response
    {
        if (($denegado = $this->autorizarMedico(true)) !== null) {
            return $denegado;
        }

        $datos = $request->datosAntecedente();

        try {
            $id = (int) Database::transaction(function () use ($datos): int {
                self::asegurarExpediente((int) $datos['id_expediente']);

                return (int) AntecedenteMedico::query()->insert($datos);
            });
        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->errorInterno('registrar el antecedente', $e);
        }

        $this->registrarBitacoraAccion(
            $this->idPersona(),
            'antecedente_medico',
            'INSERT',
            $id,
            [],
            ['id_expediente' => $datos['id_expediente'], 'tipo' => $datos['tipo']],
            'Registro de antecedente médico en expediente'
        );

        return $this->json([
            'success' => true,
            'message' => 'Antecedente registrado.',
            'id' => $id,
        ]);
    }

    /** Registro de un medicamento asociado al paciente. */
    public function guardarMedicamento(GuardarMedicamentoRequest $request): Response
    {
        if (($denegado = $this->autorizarMedico(true)) !== null) {
            return $denegado;
        }

        $datos = $request->datosMedicamento();

        try {
            $id = (int) Database::transaction(function () use ($datos): int {
                self::asegurarExpediente((int) $datos['id_expediente']);

                return (int) MedicamentoPaciente::query()->insert($datos);
            });
        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->errorInterno('registrar el medicamento', $e);
        }

        $this->registrarBitacoraAccion(
            $this->idPersona(),
            'medicamento_paciente',
            'INSERT',
            $id,
            [],
            ['id_expediente' => $datos['id_expediente'], 'nombre' => $datos['nombre_medicamento']],
            'Registro de medicamento en expediente clínico'
        );

        return $this->json([
            'success' => true,
            'message' => 'Medicamento registrado.',
            'id' => $id,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* FASE 5: HISTORIAL CLÍNICO                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Historial clínico del paciente en dos vistas complementarias:
     *  1) consultas médicas (atenciones por cita, en orden cronológico
     *     inverso, con diagnósticos, estudios y evaluaciones previas);
     *  2) expediente clínico general (perfil, alergias, antecedentes y
     *     medicamentos).
     */
    public function historial(int $id): Response
    {
        if (($denegado = $this->autorizarMedico(false)) !== null) {
            return $denegado;
        }

        $datos = self::cargarExpedientePaciente($id);

        if ($datos === null) {
            return $this->view('portal.base.layout', [
                'rolClave' => $this->rolClave(),
                'nombre' => $this->nombre(),
                'active' => 'atencion',
                'content' => View::render('portal.atencion.mensaje', [
                    'titulo' => 'Paciente no encontrado',
                    'mensaje' => 'No existe un paciente con ese identificador.',
                    'urlVolver' => route('dashboard_atencion') ?? '/portal/dashboard/atencion',
                ]),
            ]);
        }

        $consultas = self::consultasDelPaciente($id);

        // Diagnósticos, estudios y evaluaciones de las consultas listadas.
        $idsCitas = array_map(static fn (array $c): int => (int) $c['id_cita'], $consultas);
        $idsConsultas = array_values(array_filter(array_map(
            static fn (array $c): int => (int) ($c['id_consulta'] ?? 0),
            $consultas
        )));

        $diagnosticosPorConsulta = [];
        $estudiosPorConsulta = [];
        $evaluacionesPorCita = [];

        if ($idsConsultas !== []) {
            $diagnosticos = Database::raw(
                'SELECT id, id_consulta, descripcion, tipo, observaciones
                 FROM diagnostico
                 WHERE id_consulta IN (' . implode(',', array_map('intval', $idsConsultas)) . ')
                 ORDER BY CASE tipo WHEN \'PRINCIPAL\' THEN 0 ELSE 1 END, id',
                []
            );

            foreach ((array) $diagnosticos as $fila) {
                if (!is_array($fila)) {
                    continue;
                }

                $diagnosticosPorConsulta[(int) $fila['id_consulta']][] = $fila;
            }

            $estudios = Database::raw(
                'SELECT es.id, es.id_consulta, es.id_diagnostico, es.indicacion, es.estado, es.resultado,
                        es.fecha_solicitud, te.nombre AS tipo_estudio
                 FROM estudio_solicitado es
                 JOIN tipo_estudio te ON te.id = es.id_tipo_estudio
                 WHERE es.id_consulta IN (' . implode(',', array_map('intval', $idsConsultas)) . ')
                 ORDER BY es.fecha_solicitud, es.id',
                []
            );

            foreach ((array) $estudios as $fila) {
                if (!is_array($fila)) {
                    continue;
                }

                $estudiosPorConsulta[(int) $fila['id_consulta']][] = $fila;
            }
        }

        if ($idsCitas !== []) {
            $evaluaciones = Database::raw(
                'SELECT id_cita, fecha_hora, estado, motivo_referido, anamnesis_preliminar,
                        presion_sistolica, presion_diastolica, frecuencia_cardiaca,
                        frecuencia_respiratoria, temperatura, saturacion_oxigeno,
                        peso_kg, talla_cm, observaciones
                 FROM evaluacion_previa
                 WHERE id_cita IN (' . implode(',', array_map('intval', $idsCitas)) . ')
                 ORDER BY fecha_hora',
                []
            );

            foreach ((array) $evaluaciones as $fila) {
                if (!is_array($fila)) {
                    continue;
                }

                $evaluacionesPorCita[(int) $fila['id_cita']][] = $fila;
            }
        }

        foreach ($consultas as $indice => $consulta) {
            $idCitaConsulta = (int) $consulta['id_cita'];
            $idConsulta = (int) ($consulta['id_consulta'] ?? 0);

            $consultas[$indice]['diagnosticos'] = $idConsulta > 0
                ? ($diagnosticosPorConsulta[$idConsulta] ?? [])
                : [];
            $consultas[$indice]['estudios'] = $idConsulta > 0
                ? ($estudiosPorConsulta[$idConsulta] ?? [])
                : [];
            $consultas[$indice]['evaluaciones'] = $evaluacionesPorCita[$idCitaConsulta] ?? [];
        }

        return $this->view('portal.base.layout', [
            'rolClave' => $this->rolClave(),
            'nombre' => $this->nombre(),
            'active' => 'atencion',
            'content' => View::render('portal.atencion.historial', array_merge($datos, [
                'consultas' => $consultas,
                'urlExpediente' => route('portal_atencion_expediente', ['id' => $id]) ?? '',
            ])),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* CONSULTAS DE APOYO                                                   */
    /* ------------------------------------------------------------------ */

    /** Fila de la cita con su médico, con candado de fila. */
    private static function bloquearCita(int $idCita): ?array
    {
        $filas = Database::raw(
            'SELECT c.id, c.id_paciente, c.estado, c.codigo, me.id_medico
             FROM cita c
             JOIN ficha_agenda fa ON fa.id = c.id_ficha_agenda
             JOIN horario_atencion h ON h.id = fa.id_horario
             JOIN servicio_medico sm ON sm.id = h.id_servicio_medico
             JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
             WHERE c.id = :id
             FOR UPDATE OF c',
            ['id' => $idCita]
        );

        if (!is_array($filas) || $filas === [] || !is_array($filas[0])) {
            return null;
        }

        return $filas[0];
    }

    /** Fila de la recepción de la cita, con candado de fila. */
    private static function bloquearRecepcion(int $idCita): ?array
    {
        $filas = Database::raw(
            'SELECT id, id_cita, estado
             FROM recepcion_cita
             WHERE id_cita = :id
             FOR UPDATE',
            ['id' => $idCita]
        );

        if (!is_array($filas) || $filas === [] || !is_array($filas[0])) {
            return null;
        }

        return $filas[0];
    }

    /**
     * Candados comunes para editar la consulta médica de una cita:
     * bloquea cita + recepción, valida estados, propiedad del médico y
     * que la consulta exista en EN_CURSO.
     *
     * @return array{cita: array, recepcion: array, consulta: array}
     */
    private static function bloquearParaEditarConsulta(int $idCita, int $idPersona, string $rol): array
    {
        $cita = self::bloquearCita($idCita);

        if ($cita === null) {
            throw new \RuntimeException('La cita no existe.');
        }

        if ($rol === 'MEDICO' && (int) $cita['id_medico'] !== $idPersona) {
            throw new \RuntimeException('Esta consulta pertenece a otro médico.');
        }

        if ((string) $cita['estado'] !== 'EN_RECEPCION') {
            throw new \RuntimeException('La cita ya no está en recepción (estado ' . $cita['estado'] . ').');
        }

        $recepcion = self::bloquearRecepcion($idCita);

        if ($recepcion === null || (string) $recepcion['estado'] !== 'LISTO_PARA_CONSULTA') {
            throw new \RuntimeException('El paciente no está habilitado para consulta.');
        }

        $consulta = self::consultaDe($idCita);

        if ($consulta === null) {
            throw new \RuntimeException('La consulta médica no fue iniciada.');
        }

        if ((string) $consulta['estado'] !== 'EN_CURSO') {
            throw new \RuntimeException('La consulta ya fue finalizada y no puede modificarse.');
        }

        return [
            'cita' => $cita,
            'recepcion' => $recepcion,
            'consulta' => $consulta,
        ];
    }

    /**
     * Crea (si no existe) el registro de consulta de la cita en estado
     * EN_CURSO y devuelve su fila. El UNIQUE(id_cita) + ON CONFLICT
     * garantiza que nunca haya duplicados.
     */
    private static function asegurarConsulta(int $idCita): array
    {
        Database::raw(
            'INSERT INTO consulta_medica (id_cita, fecha_inicio, estado)
             VALUES (:cita, CURRENT_TIMESTAMP, \'EN_CURSO\')
             ON CONFLICT (id_cita) DO NOTHING',
            ['cita' => $idCita]
        );

        $consulta = self::consultaDe($idCita);

        if ($consulta === null) {
            throw new \RuntimeException('No se pudo crear la consulta médica.');
        }

        return $consulta;
    }

    /** Consulta médica de una cita (si existe). */
    private static function consultaDe(int $idCita): ?array
    {
        $filas = Database::raw(
            'SELECT id, id_cita, fecha_inicio, fecha_fin, anamnesis, examen_fisico,
                    evolucion, plan_tratamiento, observaciones, estado
             FROM consulta_medica
             WHERE id_cita = :cita',
            ['cita' => $idCita]
        );

        if (!is_array($filas) || $filas === [] || !is_array($filas[0])) {
            return null;
        }

        return $filas[0];
    }

    /** Evaluación previa de una cita (si existe). */
    private static function evaluacionDe(int $idCita): ?array
    {
        $filas = Database::raw(
            'SELECT id, id_cita, id_personal_registro, fecha_hora, motivo_referido,
                    anamnesis_preliminar, presion_sistolica, presion_diastolica,
                    frecuencia_cardiaca, frecuencia_respiratoria, temperatura,
                    saturacion_oxigeno, peso_kg, talla_cm, observaciones, estado
             FROM evaluacion_previa
             WHERE id_cita = :cita',
            ['cita' => $idCita]
        );

        if (!is_array($filas) || $filas === [] || !is_array($filas[0])) {
            return null;
        }

        return $filas[0];
    }

    /** Diagnósticos de una consulta ordenados (principal primero). */
    private static function diagnosticosDe(int $idConsulta): array
    {
        $filas = Database::raw(
            "SELECT id, id_consulta, descripcion, tipo, observaciones
             FROM diagnostico
             WHERE id_consulta = :consulta
             ORDER BY CASE tipo WHEN 'PRINCIPAL' THEN 0 ELSE 1 END, id",
            ['consulta' => $idConsulta]
        );

        return is_array($filas) ? $filas : [];
    }

    /** Estudios solicitados de una consulta con su tipo. */
    private static function estudiosDe(int $idConsulta): array
    {
        $filas = Database::raw(
            'SELECT es.id, es.id_consulta, es.id_diagnostico, es.id_tipo_estudio,
                    es.fecha_solicitud, es.indicacion, es.estado, es.fecha_realizacion,
                    es.resultado, es.fecha_resultado, es.observaciones,
                    te.nombre AS tipo_estudio,
                    d.descripcion AS diagnostico
             FROM estudio_solicitado es
             JOIN tipo_estudio te ON te.id = es.id_tipo_estudio
             LEFT JOIN diagnostico d ON d.id = es.id_diagnostico
             WHERE es.id_consulta = :consulta
             ORDER BY es.fecha_solicitud, es.id',
            ['consulta' => $idConsulta]
        );

        return is_array($filas) ? $filas : [];
    }

    /** Catálogo de tipos de estudio activos. */
    private static function tiposEstudio(): array
    {
        $filas = Database::raw(
            'SELECT id, nombre, descripcion
             FROM tipo_estudio
             WHERE estado = TRUE
             ORDER BY nombre'
        );

        return is_array($filas) ? $filas : [];
    }

    /** Expediente + perfil médico del paciente (si existen). */
    private static function expedienteDe(int $idPaciente): ?array
    {
        $filas = Database::raw(
            'SELECT e.id, e.id_paciente, e.fecha_apertura, e.observaciones_generales, e.estado,
                    pm.grupo_sanguineo, pm.factor_rh, pm.observaciones AS perfil_observaciones,
                    pm.fecha_actualizacion
             FROM expediente_clinico e
             LEFT JOIN perfil_medico pm ON pm.id_expediente = e.id
             WHERE e.id_paciente = :paciente',
            ['paciente' => $idPaciente]
        );

        if (!is_array($filas) || $filas === [] || !is_array($filas[0])) {
            return null;
        }

        return $filas[0];
    }

    /**
     * Carga completa del expediente de un paciente para las vistas:
     * persona, expediente, perfil, alergias, antecedentes y medicamentos.
     *
     * @return array<string, mixed>|null
     */
    private static function cargarExpedientePaciente(int $idPaciente): ?array
    {
        $persona = Database::raw(
            'SELECT p.id, p.ci, p.nombre, p.apellido, p.telefono, p.correo,
                    p.fecha_nacimiento, p.sexo, p.estado,
                    (SELECT COUNT(*) FROM cita WHERE id_paciente = p.id) AS total_citas
             FROM persona p
             JOIN paciente pa ON pa.id_persona = p.id
             WHERE p.id = :id',
            ['id' => $idPaciente]
        );

        if (!is_array($persona) || $persona === [] || !is_array($persona[0])) {
            return null;
        }

        $expediente = self::expedienteDe($idPaciente);

        $alergias = [];
        $antecedentes = [];
        $medicamentos = [];

        if ($expediente !== null) {
            $idExpediente = (int) $expediente['id'];

            $alergias = self::listarOjo(Alergia::query()
                ->where('id_expediente', $idExpediente)
                ->orderBy('id', 'desc')
                ->get());

            $antecedentes = self::listarOjo(AntecedenteMedico::query()
                ->where('id_expediente', $idExpediente)
                ->orderBy('id', 'desc')
                ->get());

            $medicamentos = self::listarOjo(MedicamentoPaciente::query()
                ->where('id_expediente', $idExpediente)
                ->orderBy('id', 'desc')
                ->get());
        }

        $personaFila = $persona[0];

        return [
            'paciente' => [
                'id' => (int) ($personaFila['id'] ?? 0),
                'ci' => (string) ($personaFila['ci'] ?? ''),
                'nombre' => (string) ($personaFila['nombre'] ?? ''),
                'apellido' => (string) ($personaFila['apellido'] ?? ''),
                'nombre_completo' => trim((string) ($personaFila['nombre'] ?? '') . ' ' . (string) ($personaFila['apellido'] ?? '')),
                'telefono' => (string) ($personaFila['telefono'] ?? ''),
                'correo' => (string) ($personaFila['correo'] ?? ''),
                'fecha_nacimiento' => (string) ($personaFila['fecha_nacimiento'] ?? ''),
                'sexo' => (string) ($personaFila['sexo'] ?? ''),
                'total_citas' => (int) ($personaFila['total_citas'] ?? 0),
            ],
            'expediente' => $expediente,
            'alergias' => $alergias,
            'antecedentes' => $antecedentes,
            'medicamentos' => $medicamentos,
        ];
    }

    /** Asegura que el expediente exista (para inserts relacionados). */
    private static function asegurarExpediente(int $idExpediente): void
    {
        $filas = Database::raw(
            'SELECT id FROM expediente_clinico WHERE id = :id',
            ['id' => $idExpediente]
        );

        if (!is_array($filas) || $filas === []) {
            throw new \RuntimeException('El expediente clínico no existe. Créalo primero.');
        }
    }

    /**
     * Atenciones del paciente por consulta médica (FASE 5), en orden
     * cronológico inverso: cita, ficha, médico, especialidad, consulta,
     * recepción y evaluación previa.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function consultasDelPaciente(int $idPaciente): array
    {
        $filas = Database::raw(
            "SELECT c.id AS id_cita,
                    c.codigo,
                    c.estado AS cita_estado,
                    c.motivo_consulta,
                    c.fecha_registro,
                    fa.fecha,
                    fa.fecha_hora_inicio,
                    fa.fecha_hora_fin,
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
               AND c.estado IN ('EN_RECEPCION', 'EN_ATENCION', 'ATENDIDA')
             ORDER BY fa.fecha_hora_inicio DESC, c.id DESC",
            ['paciente' => $idPaciente]
        );

        return is_array($filas) ? $filas : [];
    }

    /** Filas de un resultado garantizando array de arrays. */
    private static function listarOjo(mixed $filas): array
    {
        $items = [];

        foreach ((array) $filas as $fila) {
            if (is_array($fila)) {
                $items[] = $fila;
            }
        }

        return $items;
    }

    /* ------------------------------------------------------------------ */
    /* AUTORIZACIÓN Y APOYO                                                 */
    /* ------------------------------------------------------------------ */

    /** Recepción/administración del flujo (RECEP y ADMIN). */
    private function autorizarRecepcion(bool $json): ?Response
    {
        if (in_array($this->rolClave(), ['RECEP', 'ADMIN'], true)) {
            return null;
        }

        if ($json) {
            return $this->json(['success' => false, 'message' => 'Esta acción es exclusiva de recepción.'], 403);
        }

        return Response::redirect(route('portal_dashboard') ?? '/portal/dashboard');
    }

    /** Interfaz médica del flujo (MEDICO y ADMIN). */
    private function autorizarMedico(bool $json): ?Response
    {
        if (in_array($this->rolClave(), ['MEDICO', 'ADMIN'], true)) {
            return null;
        }

        if ($json) {
            return $this->json(['success' => false, 'message' => 'Esta sección es exclusiva del personal médico.'], 403);
        }

        return Response::redirect(route('portal_dashboard') ?? '/portal/dashboard');
    }

    /** Cualquiera de los roles del flujo (recepción, médico o admin). */
    private function autorizarFlujo(bool $json): ?Response
    {
        if (in_array($this->rolClave(), ['RECEP', 'MEDICO', 'ADMIN'], true)) {
            return null;
        }

        if ($json) {
            return $this->json(['success' => false, 'message' => 'No tienes permiso para esta sección.'], 403);
        }

        return Response::redirect(route('portal_dashboard') ?? '/portal/dashboard');
    }

    /**
     * El médico solo puede operar sobre consultas de sus propias citas
     * (el administrador puede operar sobre todas).
     */
    private function autorizarPropietario(int $idMedicoCita): ?Response
    {
        if ($this->rolClave() === 'ADMIN') {
            return null;
        }

        if ($this->rolClave() === 'MEDICO' && $idMedicoCita === $this->idPersona()) {
            return null;
        }

        return $this->json([
            'success' => false,
            'message' => 'Esta consulta pertenece a otro médico.',
        ], 403);
    }

    /**
     * Variante para usar dentro de transacciones: lanza RuntimeException
     * (que el controlador convierte en 422) si la cita no le pertenece.
     */
    private function exigirPropietario(int $idMedicoCita): void
    {
        if ($this->autorizarPropietario($idMedicoCita) !== null) {
            throw new \RuntimeException('Esta consulta pertenece a otro médico.');
        }
    }

    /** Respuesta 500 uniforme con detalle opcional en debug. */
    private function errorInterno(string $accion, \Throwable $e): Response
    {
        error_log('Error al ' . $accion . ': ' . $e->getMessage());

        $mensaje = 'No se pudo ' . $accion . '. Recarga e intenta de nuevo.';

        if (config('app.debug')) {
            $mensaje .= ' [debug] ' . $e->getMessage();
        }

        return $this->json(['success' => false, 'message' => $mensaje], 500);
    }

    private function nombre(): string
    {
        return (string) \Lib\Support\Session::get('portal_nombre', 'Usuario');
    }
}
