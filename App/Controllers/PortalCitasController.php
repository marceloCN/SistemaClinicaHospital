<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\FichaAgenda;
use App\Models\MedicoEspecialidad;
use App\Models\ServicioMedico;
use App\Requests\CancelarCitaRequest;
use App\Requests\GenerarCitaRequest;
use Lib\Core\Database;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\View;

class PortalCitasController extends Controller
{
    private const POR_PAGINA = 10;

    /**
     * en esta seccion se va a generar citas medicas por parte de los pacientes, en funcion a la especialidad, el medico, y fecha y hora de la cita medica
     * el paciente no puede reprogramar su cita medica, pero si puede cancelar su cita medica, del cual su ficha medica vuelve a estado = ABIERTO
     * entre los metodos que va tener el controller es:
     * index() -> estadistica de las citas historicas del paciente, las citas pendientes, citas canceladas entre otros
     * generarCita() -> el paciente puede generar una cita medica de 2 maneras, uso de la voz o por un conjunto de pasos, (paso1: especialidad, paso2: medico, paso3: fecha y hora de la cita medica)
     * el paso 3 ten cuidado porque n pacientes pueden generar la misma cita medica, por lo que ese parametro debe estar verificando y validando que la cita medica este disponible, y no 
     * exista duplicidad de cita medica entre pacientes en el mismo horario y fecha.
     * historial de citas medicas -> el paciente puede ver su historial de citas medicas, y ver el detalle en funcion  de la ficha medica.
     */

    /**
     * Panel del paciente: estadísticas de sus citas y próximos turnos.
     */
    public function index(Request $request): Response
    {
        $idPaciente = $this->idPersona();

        return $this->view('portal.base.layout', [
            'rolClave' => $this->rolClave(),
            'nombre' => $this->nombre(),
            'active' => 'citas-medicas',
            'content' => View::render('portal.citas-medicas.index', [
                'estadisticas' => Cita::estadisticasPaciente($idPaciente),
                'proximas' => Cita::proximasPaciente($idPaciente),
                //'catalogoEspecialidades' => Especialidad::activas(),
                'catalogoEspecialidades' => ServicioMedico::especialidadesActivas(),
            ]),
        ]);
    }

    /**
     * Historial de citas del paciente con filtros y paginación.
     */
    public function misCitas(Request $request): Response
    {
        $idPaciente = $this->idPersona();
        $pagina = max(1, (int) $request->get('page', 1));
        $filtros = $this->filtros($request);
        $listado = Cita::listarPorPaciente($idPaciente, $filtros, $pagina, self::POR_PAGINA);

        return $this->view('portal.base.layout', [
            'rolClave' => $this->rolClave(),
            'nombre' => $this->nombre(),
            'active' => 'mis-citas',
            'content' => View::render('portal.citas-medicas.mis-citas', [
                'estadisticas' => Cita::estadisticasPaciente($idPaciente),
                'citas' => $listado['items'],
                'total' => $listado['total'],
                'pagina' => $listado['pagina'],
                'paginas' => $listado['paginas'],
                'porPagina' => $listado['porPagina'],
                'numerosPagina' => $this->numerosPagina($listado['pagina'], $listado['paginas']),
                'filtroEstado' => $filtros['estado'],
                'filtroDesde' => $filtros['desde'],
                'filtroHasta' => $filtros['hasta'],
                'filtroTexto' => $filtros['texto'],
                'estadosDisponibles' => [
                    'PROGRAMADA' => 'Programada',
                    'CONFIRMADA' => 'Confirmada',
                    'REPROGRAMADA' => 'Reprogramada',
                    'EN_RECEPCION' => 'En recepción',
                    'EN_ATENCION' => 'En atención',
                    'ATENDIDA' => 'Atendida',
                    'CANCELADA' => 'Cancelada',
                    'INASISTENCIA' => 'Inasistencia',
                ],
            ]),
        ]);
    }

    /**
     * Reserva de la cita (paso 3 del asistente).
     *
     * El FormRequest valida CSRF y campos; aquí se hace la transacción
     * con candado FOR UPDATE sobre la ficha: si dos pacientes aprietan
     * "Confirmar" al mismo tiempo, el segundo espera a que el primero
     * confirme y recibe el mensaje de que el turno ya no está disponible.
     */
    public function generarCita(GenerarCitaRequest $request): Response
    {
        $rol = $this->rolClave();
        $esRecepcion = in_array($rol, ['RECEP', 'ADMIN'], true);
        $datos = $request->datosCita();


        if ($esRecepcion) {
            $idPaciente = (int) ($datos['id_paciente'] ?? 0);
            if ($idPaciente <= 0) {
                return $this->json(['success' => false, 'message' => 'Selecciona al paciente.'], 422);
            }
        } else {
            $idPaciente = $this->idPersona();
        }

        //falta verificar si ese paciente anteriormente ya contaba con cita previa con ese doctor de servicio medico y ese horario

        try {
            $idCita = Cita::reservar($datos['id_ficha_agenda'], $idPaciente, $this->idPersona(), $datos['motivo_consulta']);
        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            error_log('Error al generar cita: ' . $e->getMessage());

            $mensaje = 'No se pudo generar la cita. Es posible que el turno se haya tomado al mismo tiempo; elige otro horario.';

            if (config('app.debug')) {
                $mensaje .= ' [debug] ' . $e->getMessage();
            }

            return $this->json(['success' => false, 'message' => $mensaje], 500);
        }

        $this->registrarBitacoraAccion($this->idPersona(), 'cita', 'INSERT', $idCita, [], [
            'id_ficha_agenda' => $datos['id_ficha_agenda'],
            'motivo_consulta' => $datos['motivo_consulta'],
        ], $esRecepcion ? 'Generación de cita médica por recepción' : 'Generación de cita médica por el paciente');

        return $this->json([
            'success' => true,
            'message' => 'Cita médica generada correctamente. Guarda tu código para la recepción.',
            'id' => $idCita,
        ]);
    }

    /**
     * Cancela una cita del propio paciente: la ficha vuelve a ABIERTA.
     */
    public function cancelarCita(CancelarCitaRequest $request, int $id): Response
    {
        if ($id <= 0) {
            return $this->json(['success' => false, 'message' => 'Cita no válida.'], 404);
        }

        $idPaciente = $this->idPersona();

        try {
            Database::transaction(function () use ($id, $idPaciente): void {
                $cita = Database::raw(
                    'SELECT id_ficha_agenda
                     FROM cita WHERE id = :id AND id_paciente = :paciente',
                    ['id' => $id, 'paciente' => $idPaciente]
                );

                $idFicha = (int) $cita[0]['id_ficha_agenda'];

                Cita::query()->update(['estado' => 'CANCELADA'], ['id' => $id]);
                FichaAgenda::query()->update(['estado' => 'ABIERTA'], ['id' => $idFicha]);
            });
        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            error_log('Error al cancelar cita: ' . $e->getMessage());

            $mensaje = 'No se pudo cancelar la cita. Recarga e intenta de nuevo.';

            if (config('app.debug')) {
                $mensaje .= ' [debug] ' . $e->getMessage();
            }

            return $this->json(['success' => false, 'message' => $mensaje], 500);
        }

        $this->registrarBitacoraAccion($this->idPersona(), 'cita', 'UPDATE', $id, [], [
            'estado' => 'CANCELADA',
        ], 'Cancelación de cita médica por el paciente');

        return $this->json([
            'success' => true,
            'message' => 'Cita cancelada; el turno volvió a estar disponible para otros pacientes.',
        ]);
    }

    /**
     * Detalle de una cita propia (modal desde Mis citas / Próximas).
     */
    public function verDetalle(Request $request, int $id): Response
    {
        if ($id <= 0) {
            return $this->json(['success' => false, 'message' => 'Cita no válida.'], 404);
        }

        $cita = Cita::detalle($id);

        if ($cita === null) {
            return $this->json(['success' => false, 'message' => 'La cita no existe.']);
        }

        return $this->json([
            'success' => true,
            'cita' => $cita,
        ]);
    }

    /**
     * Médicos acreditados en una especialidad (paso 2 del asistente).
     */
    public function medicosPorEspecialidad(Request $request): Response
    {
        $idEspecialidad = (int) $request->get('id_especialidad', 0);


        if ($idEspecialidad <= 0) {
            return $this->json(['success' => false, 'message' => 'Selecciona una especialidad.'], 422);
        }


        return $this->json([
            'success' => true,
            'medicos' => ServicioMedico::medicosPorEspecialidad($idEspecialidad),
        ]);
    }

    /**
     * Fichas libres de un médico para una especialidad y fecha
     * (paso 3 del asistente).
     */
    public function fichasDisponibles(Request $request): Response
    {
        $idMedico = (int) $request->get('id_medico', 0);
        $fecha = trim((string) $request->get('fecha', ''));

        if ($idMedico <= 0) {
            return $this->json(['success' => false, 'message' => 'Selecciona el médico y la especialidad.']);
        }

        if (!$this->fechaValida($fecha)) {
            return $this->json(['success' => false, 'message' => 'La fecha no es válida.']);
        }

        return $this->json([
            'success' => true,
            'fichas' => FichaAgenda::fichasDisponibles($idMedico, $fecha),
        ]);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Filtros del historial de citas del paciente.
     *
     * @return array{estado: string, desde: string, hasta: string, texto: string}
     */
    private function filtros(Request $request): array
    {
        $estado = trim((string) $request->get('estado', ''));
        $desde = trim((string) $request->get('desde', ''));
        $hasta = trim((string) $request->get('hasta', ''));
        $texto = trim((string) $request->get('texto', ''));

        $estadosValidos = [
            'PROGRAMADA',
            'CONFIRMADA',
            'REPROGRAMADA',
            'EN_RECEPCION',
            'EN_ATENCION',
            'ATENDIDA',
            'CANCELADA',
            'INASISTENCIA',
        ];

        if (!in_array($estado, $estadosValidos, true)) {
            $estado = '';
        }

        if (!$this->fechaValida($desde)) {
            $desde = '';
        }

        if (!$this->fechaValida($hasta)) {
            $hasta = '';
        }

        if ($desde !== '' && $hasta !== '' && $hasta < $desde) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        if ($texto !== '') {
            $texto = mb_substr($texto, 0, 100);
        }

        return [
            'estado' => $estado,
            'desde' => $desde,
            'hasta' => $hasta,
            'texto' => $texto,
        ];
    }

    private function nombre(): string
    {
        return (string) \Lib\Support\Session::get('portal_nombre', 'Usuario');
    }
}
