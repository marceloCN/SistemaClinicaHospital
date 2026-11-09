<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\BloqueoAgenda;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\FichaAgenda;
use App\Models\Paciente;
use App\Models\RecepcionCita;
use App\Requests\RecepcionarCitaRequest;
use App\Requests\RegistrarCitaRequest;
use App\Requests\ReprogramarCitaRequest;
use Lib\Core\Database;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\View;

class PortalRecepcionCitasController extends Controller
{
    private const POR_PAGINA = 10;

/*
en este controlador se va a manejar la recepcion de citas medicas, va trabajar con las tablas de recepcion_cita, citas y evaluacion_previa
del cual tambien este se va a encargar de generar la reprogramacion de cita ante alguna insertidumbre de bloqueo_agenda (es decir que el medico
coloque su excusa de porque no puede asistir en esas fichas, y este sera modificado en esta situacion, y se le notificara al paciente que su cita
fue modificada y este le cambiara la fecha y hora de su cita medica en funcion al recepcionista de la clinica.)

- por lo tanto necesito las siguientes funciones en este controlador:
- index() -> estadistica diaria de la recepcion de citas medicas, en funcion al dia, cuales fueron atendidas, reprogramadas, canceladas
- registrarCitaMedica() -> la recepcionista puede registrar una cita medica para un paciente, en funcion a la especialidad, el medico, fecha y hora
de la cita medica, y este se va guardar en la tabla de cita, del cual con su mismo codigo puede recepcionar la cita medica
- reprogramarCitaMedica() -> la recepcionista puede reprogramar una cita medica para un paciente, en funcion al bloqueo de agenda del medico
del cual puede cambiar todas las fichas que ya estan en estado CERRADO a BLOQUEADA y mandar al nuevo horario disponible del medico, si no cuenta detener la situacion,
pero si tiene pal siguiente turno, pasando su bloqueo de agenda. 
*/

    /**
     * Panel diario de la recepción: estadísticas del día, citas del día
     * y bloqueos de agenda activos.
     */
    public function index(Request $request): Response
    {
        if (($denegado = $this->soloRecepcion(false)) !== null) {
            return $denegado;
        }

        $pagina = max(1, (int) $request->get('page', 1));
        $filtros = $this->filtros($request);
        $listado = Cita::listarHoy($filtros, $pagina, self::POR_PAGINA);

        return $this->view('portal.base.layout', [
            'rolClave' => $this->rolClave(),
            'nombre' => $this->nombre(),
            'active' => 'recepcion-citas',
            'content' => View::render('portal.recepcion-cita-medica.index', [
                'estadisticas' => Cita::estadisticasDia(),
                'citas' => $listado['items'],
                'total' => $listado['total'],
                'pagina' => $listado['pagina'],
                'paginas' => $listado['paginas'],
                'porPagina' => $listado['porPagina'],
                'numerosPagina' => $this->numerosPagina($listado['pagina'], $listado['paginas']),
                'filtroEstado' => $filtros['estado'],
                'filtroTexto' => $filtros['texto'],
                'bloqueos' => BloqueoAgenda::activos(),
                'catalogoEspecialidades' => Especialidad::activas(),
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
     * Búsqueda previa de una cita por su código para mostrarla en el
     * modal de check-in antes de confirmar la recepción.
     */
    public function buscarPorCodigo(Request $request): Response
    {
        $codigo = trim((string) $request->get('codigo', ''));

        if ($codigo === '') {
            return $this->json(['success' => false, 'message' => 'Ingresa el código de la cita.'], 422);
        }

        $cita = Cita::buscarPorCodigo($codigo);

        if ($cita === null) {
            return $this->json(['success' => false, 'message' => 'No existe una cita con ese código.'], 404);
        }

        $recepcion = Cita::recepcionDe((int) $cita['id']);

        return $this->json([
            'success' => true,
            'cita' => $cita,
            'recepcionada' => $recepcion !== null,
            'puede_recepcionar' => $cita['estado'] === 'PROGRAMADA' && $recepcion === null,
        ]);
    }

    /**
     * Check-in de una cita en la recepción (inserta recepcion_cita y
     * cambia la cita a EN_RECEPCION).
     */
    public function recepcionarCitaMedica(RecepcionarCitaRequest $request): Response
    {
        $codigo = $request->datosRecepcion()['codigo'];

        try {
            Database::transaction(function () use ($codigo): void {
                $cita = Database::raw(
                    'SELECT id, estado FROM cita WHERE codigo = :codigo FOR UPDATE',
                    ['codigo' => $codigo]
                );

                if (!is_array($cita) || $cita === [] || !is_array($cita[0])) {
                    throw new \RuntimeException('No existe una cita con ese código.');
                }

                $idCita = (int) $cita[0]['id'];
                $estado = (string) $cita[0]['estado'];

                if (in_array($estado, ['REPROGRAMADA', 'CANCELADA'], true)) {
                    throw new \RuntimeException('La cita ya no puede recepcionarse (estado ' . $estado . ').');
                }

                $recepcion = Cita::recepcionDe($idCita);

                if ($recepcion !== null) {
                    throw new \RuntimeException('La cita ya fue recepcionada anteriormente.');
                }

                RecepcionCita::query()->insert([
                    'id_cita' => $idCita,
                    'id_usuario_recibe' => $this->idPersona(),
                    'estado' => 'EN_ESPERA',
                ]);

                Cita::query()->update(['estado' => 'EN_RECEPCION'], ['id' => $idCita]);
            });
        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            error_log('Error al recepcionar cita: ' . $e->getMessage());

            $mensaje = 'No se pudo recepcionar la cita. Recarga e intenta de nuevo.';

            if (config('app.debug')) {
                $mensaje .= ' [debug] ' . $e->getMessage();
            }

            return $this->json(['success' => false, 'message' => $mensaje], 500);
        }

        $cita = Cita::buscarPorCodigo($codigo);
        $idCita = (int) ($cita['id'] ?? 0);

        $this->registrarBitacoraAccion($this->idPersona(), 'recepcion_cita', 'INSERT', $idCita, [], [
            'codigo' => $codigo,
            'estado' => 'EN_RECEPCION',
        ], 'Recepción de cita médica en ventanilla');

        return $this->json([
            'success' => true,
            'message' => 'Cita recepcionada correctamente.',
            'cita' => $cita,
        ]);
    }

    /**
     * Cambio manual de estado de una cita (ej. INASISTENCIA) por parte de
     * recepción. La ficha/agenda asociada mantiene su estado.
     */
    public function cambiarEstadoCita(Request $request): Response
    {
        $idCita = (int) $request->input('id_cita', 0);
        $estado = trim((string) $request->input('estado', ''));

        if ($idCita <= 0) {
            return $this->json([
                'success' => false,
                'message' => 'El identificador de la cita no es válido.',
            ]);
        }

        try {
            Database::transaction(function () use ($idCita, $estado): void {
                Database::raw(
                    'UPDATE cita SET estado = :estado WHERE id = :id',
                    ['estado' => $estado, 'id' => $idCita]
                );
            });
        } catch (\Throwable $e) {
            error_log('Error al cambiar estado de cita: ' . $e->getMessage());

            $mensaje = 'No se pudo cambiar el estado de la cita.';
            if (config('app.debug')) {
                $mensaje .= ' [debug] ' . $e->getMessage();
            }

            return $this->json(['success' => false, 'message' => $mensaje], 500);
        }

        $this->registrarBitacoraAccion($this->idPersona(), 'cita', 'UPDATE', $idCita, [], [
            'estado' => $estado,
        ], 'Cambio de estado de cita médica por recepción');

        return $this->json([
            'success' => true,
            'message' => 'Estado de la cita actualizado correctamente.',
        ]);
    }

    /**
     * Buscador de pacientes para el registro de citas.
     */
    public function buscarPaciente(Request $request): Response
    {
        $texto = trim((string) $request->get('texto', ''));

        return $this->json([
            'success' => true,
            'pacientes' => $texto === '' ? [] : Paciente::buscar($texto),
        ]);
    }

    /**
     * Médicos acreditados en una especialidad (registro de citas en
     * recepción; paso 2 del asistente).
     */
    /*
    public function medicosPorEspecialidad(Request $request): Response
    {
        if (($denegado = $this->soloRecepcion(true)) !== null) {
            return $denegado;
        }

        $idEspecialidad = (int) $request->get('id_especialidad', 0);

        if ($idEspecialidad <= 0) {
            return $this->json(['success' => false, 'message' => 'Selecciona una especialidad.'], 422);
        }

        return $this->json([
            'success' => true,
            'medicos' => MedicoEspecialidad::medicosPorEspecialidad($idEspecialidad),
        ]);
    }*/

    /**
     * Fichas libres de un médico para una especialidad y fecha
     * (registro de citas en recepción; paso 3 del asistente).
     */
    /*
    public function fichasDisponibles(Request $request): Response
    {

        $idMedico = (int) $request->get('id_medico', 0);
        $idEspecialidad = (int) $request->get('id_especialidad', 0);
        $fecha = trim((string) $request->get('fecha', ''));

        if ($idMedico <= 0 || $idEspecialidad <= 0) {
            return $this->json(['success' => false, 'message' => 'Selecciona el médico y la especialidad.'], 422);
        }

        if (!$this->fechaValida($fecha)) {
            return $this->json(['success' => false, 'message' => 'La fecha no es válida.'], 422);
        }

        return $this->json([
            'success' => true,
            'fichas' => FichaAgenda::disponibles($idMedico, $idEspecialidad, $fecha),
        ]);
    }

    */

    /**
     * Registro de una cita médica para un paciente desde la recepción.
     *
     * Reutiliza la misma transacción con candado de ficha que el
     * asistente del paciente.
     */
    /*
    public function registrarCitaMedica(RegistrarCitaRequest $request): Response
    {
        if (($denegado = $this->soloRecepcion(true)) !== null) {
            return $denegado;
        }

        $datos = $request->datosRegistro();

        try {
            $idCita = Cita::reservar(
                $datos['id_ficha_agenda'],
                $datos['id_paciente'],
                $this->idPersona(),
                $datos['motivo_consulta']
            );
        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            error_log('Error al registrar cita: ' . $e->getMessage());

            $mensaje = 'No se pudo registrar la cita. Es posible que el turno se haya tomado al mismo tiempo; elige otro horario.';

            if (config('app.debug')) {
                $mensaje .= ' [debug] ' . $e->getMessage();
            }

            return $this->json(['success' => false, 'message' => $mensaje], 500);
        }

        $cita = Cita::detalle($idCita);

        $this->registrarBitacoraAccion($this->idPersona(), 'cita', 'INSERT', $idCita, [], [
            'id_paciente' => $datos['id_paciente'],
            'id_ficha_agenda' => $datos['id_ficha_agenda'],
            'motivo_consulta' => $datos['motivo_consulta'],
        ], 'Registro de cita médica desde recepción');

        return $this->json([
            'success' => true,
            'message' => 'Cita registrada correctamente para el paciente.',
            'cita' => $cita,
        ]);
    }
        */

    /**
     * Listado de bloqueos activos (para refrescar el panel de
     * reprogramación sin recargar toda la página).
     */
    /*
    public function listarBloqueos(Request $request): Response
    {

        return $this->json([
            'success' => true,
            'bloqueos' => BloqueoAgenda::activos(),
        ]);
    }*/

    /**
     * Citas afectadas por un bloqueo de agenda (modal de reprogramación).
     */
    public function citasAfectadasPorBloqueo(Request $request, int $id): Response
    {
        if ($this->rolClave() !== 'ADMIN') {
            return $this->json(['success' => false, 'message' => 'Esta sección es exclusiva del administrador.']);
        }

        if ($id <= 0) {
            return $this->json(['success' => false, 'message' => 'Bloqueo no válido.'], 404);
        }

        $bloqueo = BloqueoAgenda::activo($id);

        if ($bloqueo === null) {
            return $this->json(['success' => false, 'message' => 'El bloqueo no existe o ya no está activo.'], 404);
        }

        return $this->json([
            'success' => true,
            'bloqueo' => $bloqueo,
            'citas' => Cita::afectadasPorBloqueo(
                (int) $bloqueo['id_servicio_medico'],
                (string) $bloqueo['inicio_crudo'],
                (string) $bloqueo['fin_crudo']
            ),
        ]);
    }

    /**
     * Reprograma una cita a una ficha nueva: la cita original pasa a estado
     * REPROGRAMADA dejando su ficha original BLOQUEADA, y se crea una cita
     * nueva en estado PROGRAMADA con la ficha elegida.
     */
    public function reprogramarCitaMedica(ReprogramarCitaRequest $request): Response
    {
        $datos = $request->datosReprogramacion();

        try {
            $resultado = Database::transaction(function () use ($datos): array {
                $cita = Database::raw(
                    'SELECT c.id,
                            c.estado,
                            c.id_paciente,
                            c.id_ficha_agenda,
                            c.motivo_consulta,
                            c.observaciones,
                            me.id_especialidad
                     FROM cita c
                     JOIN ficha_agenda fa ON fa.id = c.id_ficha_agenda
                     JOIN horario_atencion h ON h.id = fa.id_horario
                     JOIN servicio_medico sm ON sm.id = h.id_servicio_medico
                     JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
                     WHERE c.id = :id FOR UPDATE',
                    ['id' => $datos['id_cita']]
                );

                if (empty($cita)) {
                    throw new \RuntimeException('La cita no existe.');
                }

                $estado = (string) $cita[0]['estado'];
                if (!in_array($estado, ['PROGRAMADA', 'REPROGRAMADA'], true)) {
                    throw new \RuntimeException('Solo se pueden reprogramar citas en estado PROGRAMADA o REPROGRAMADA.');
                }

                $idFichaVieja = (int) $cita[0]['id_ficha_agenda'];
                $idFichaNueva = $datos['id_ficha_nueva'];

                $ocupadas = Database::raw(
                    "SELECT COUNT(*) AS total FROM cita
                     WHERE id_ficha_agenda = :ficha
                       AND estado NOT IN ('CANCELADA', 'INASISTENCIA')",
                    ['ficha' => $idFichaNueva]
                );

                if ((int) ($ocupadas[0]['total'] ?? 0) > 0) {
                    throw new \RuntimeException('El turno destino ya fue reservado por otro paciente.');
                }

                // Cita original: REPROGRAMADA, mantiene su ficha original.
                Cita::query()->update([
                    'estado' => 'REPROGRAMADA',
                ], ['id' => $datos['id_cita']]);

                // Ficha original bloqueada; ficha nueva cerrada.
                FichaAgenda::query()->update(['estado' => 'BLOQUEADA'], ['id' => $idFichaVieja]);
                FichaAgenda::query()->update(['estado' => 'CERRADA'], ['id' => $idFichaNueva]);

                // Nueva cita con los mismos datos y la ficha elegida.
                $idNuevaCita = (int) Cita::query()->insert([
                    'codigo' => Cita::nuevoCodigo((int) ($cita[0]['id_especialidad'] ?? 0)),
                    'id_paciente' => (int) $cita[0]['id_paciente'],
                    'id_ficha_agenda' => $idFichaNueva,
                    'estado' => 'PROGRAMADA',
                    'motivo_consulta' => (string) $cita[0]['motivo_consulta'],
                    'id_usuario_registro' => $this->idPersona(),
                    'observaciones' => (string) ($cita[0]['observaciones'] ?? ''),
                ]);

                // Bitácora de reprogramación asociada a la cita nueva.
                Database::raw(
                    'INSERT INTO reprogramacion_cita
                     (id_cita, id_ficha_anterior, id_ficha_nueva, id_usuario_reprograma, motivo)
                     VALUES (:cita, :anterior, :nueva, :usuario, :motivo)',
                    [
                        'cita' => $idNuevaCita,
                        'anterior' => $idFichaVieja,
                        'nueva' => $idFichaNueva,
                        'usuario' => $this->idPersona(),
                        'motivo' => $datos['motivo'],
                    ]
                );

                return [
                    'id_cita_original' => $datos['id_cita'],
                    'id_cita_nueva' => $idNuevaCita,
                    'id_ficha_nueva' => $idFichaNueva,
                    'motivo' => $datos['motivo'],
                ];
            });
        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            error_log('Error al reprogramar cita: ' . $e->getMessage());

            $mensaje = 'No se pudo reprogramar la cita. Recarga e intenta de nuevo.';

            if (config('app.debug')) {
                $mensaje .= ' [debug] ' . $e->getMessage();
            }

            return $this->json(['success' => false, 'message' => $mensaje], 500);
        }

        $this->registrarBitacoraAccion(
            $this->idPersona(),
            'cita',
            'UPDATE',
            $resultado['id_cita_original'],
            [],
            [
                'id_cita_nueva' => $resultado['id_cita_nueva'],
                'id_ficha_nueva' => $resultado['id_ficha_nueva'],
                'estado_original' => 'REPROGRAMADA',
                'motivo' => $resultado['motivo'],
            ],
            'Reprogramación de cita médica por recepción'
        );

        return $this->json([
            'success' => true,
            'message' => 'Cita reprogramada correctamente.',
        ]);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Filtros de la tabla de citas del día.
     *
     * @return array{estado: string, texto: string}
     */
    private function filtros(Request $request): array
    {
        $estado = trim((string) $request->get('estado', ''));
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

        if ($texto !== '') {
            $texto = mb_substr($texto, 0, 100);
        }

        return [
            'estado' => $estado,
            'texto' => $texto,
        ];
    }

    /** Autorización: recepción solo para RECEP y ADMIN. */
    private function soloRecepcion(bool $json): ?Response
    {
        if (in_array($this->rolClave(), ['RECEP', 'ADMIN'], true)) {
            return null;
        }

        if ($json) {
            return $this->json(['success' => false, 'message' => 'Esta sección es exclusiva de recepción.'], 403);
        }

        return Response::redirect(route('portal_dashboard') ?? '/portal/dashboard');
    }

    private function nombre(): string
    {
        return (string) \Lib\Support\Session::get('portal_nombre', 'Usuario');
    }
}
