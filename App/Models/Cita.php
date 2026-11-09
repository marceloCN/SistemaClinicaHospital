<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Database;
use Lib\Core\Model;

class Cita extends Model
{
    protected string $table = 'cita';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'codigo',
        'id_paciente',
        'id_ficha_agenda',
        'estado',
        'motivo_consulta',
        'id_usuario_registro',
        'observaciones',
    ];

    protected bool $timestamps = false;

    /** Estados que el paciente puede cancelar (y que la recepción puede recibir). */
    public const ESTADOS_CANCELABLES = ['PROGRAMADA', 'CONFIRMADA', 'REPROGRAMADA'];

    /** Estados que cuentan como cita ocupada/activa de un paciente. */
    public const ESTADOS_OCUPADOS = ['PROGRAMADA', 'CONFIRMADA', 'REPROGRAMADA', 'EN_RECEPCION', 'EN_ATENCION', 'ATENDIDA'];

    /** Estados que se pueden mover en una reprogramación por bloqueo. */
    public const ESTADOS_REPROGRAMABLES = ['PROGRAMADA', 'REPROGRAMADA'];

    /** SELECT base con ficha, horario, médico, especialidad y paciente. */
    private const SELECT_CITA = "
        SELECT c.id,
               c.codigo,
               c.id_paciente,
               c.id_ficha_agenda,
               c.estado,
               c.motivo_consulta,
               c.id_usuario_registro,
               c.fecha_registro,
               c.observaciones,
               fa.fecha,
               fa.fecha_hora_inicio,
               fa.fecha_hora_fin,
               fa.estado AS ficha_estado,
               h.id_servicio_medico,
               e.id AS id_especialidad,
               me.id_medico,
               (p.nombre || ' ' || p.apellido) AS paciente,
               p.ci AS paciente_ci,
               (mp.nombre || ' ' || mp.apellido) AS medico,
               e.nombre AS especialidad,
               m.nro_colegiado
        FROM cita c
        JOIN ficha_agenda fa ON fa.id = c.id_ficha_agenda
        JOIN horario_atencion h ON h.id = fa.id_horario
        JOIN servicio_medico sm ON sm.id = h.id_servicio_medico
        JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
        JOIN especialidad e ON e.id = me.id_especialidad
        JOIN medico m ON m.id_persona = me.id_medico
        JOIN persona mp ON mp.id = m.id_persona
        JOIN paciente pac ON pac.id_persona = c.id_paciente
        JOIN persona p ON p.id = pac.id_persona
    ";

    /**
     * Conteos de las citas de un paciente para el panel de estadísticas.
     *
     * @return array{total: int, pendientes: int, en_clinica: int, atendidas: int, canceladas: int, inasistencias: int}
     */
    public static function estadisticasPaciente(int $idPaciente): array
    {
        $estadisticas = [
            'total' => 0,
            'pendientes' => 0,
            'en_clinica' => 0,
            'atendidas' => 0,
            'canceladas' => 0,
            'inasistencias' => 0,
        ];

        $filas = Database::raw(
            'SELECT COUNT(*) AS total,
                    COUNT(CASE WHEN estado IN (\'PROGRAMADA\', \'CONFIRMADA\', \'REPROGRAMADA\') THEN 1 END) AS pendientes,
                    COUNT(CASE WHEN estado IN (\'EN_RECEPCION\', \'EN_ATENCION\') THEN 1 END) AS en_clinica,
                    COUNT(CASE WHEN estado = \'ATENDIDA\' THEN 1 END) AS atendidas,
                    COUNT(CASE WHEN estado = \'CANCELADA\' THEN 1 END) AS canceladas,
                    COUNT(CASE WHEN estado = \'INASISTENCIA\' THEN 1 END) AS inasistencias
             FROM cita
             WHERE id_paciente = :paciente',
            ['paciente' => $idPaciente]
        );

        if (is_array($filas) && isset($filas[0]) && is_array($filas[0])) {
            foreach (array_keys($estadisticas) as $clave) {
                $estadisticas[$clave] = (int) ($filas[0][$clave] ?? 0);
            }
        }

        return $estadisticas;
    }

    /**
     * Próximas citas activas de un paciente (pantalla de inicio).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function proximasPaciente(int $idPaciente, int $limite = 5): array
    {
        $limite = max(1, min(20, $limite));

        $filas = Database::raw(
            self::SELECT_CITA . "
             WHERE c.id_paciente = :paciente
               AND c.estado IN ('PROGRAMADA', 'CONFIRMADA', 'REPROGRAMADA')
               AND fa.fecha_hora_inicio >= NOW()
             ORDER BY fa.fecha_hora_inicio
             LIMIT {$limite}",
            ['paciente' => $idPaciente]
        );

        return self::normalizarFilas($filas);
    }

    /**
     * Historial de citas de un paciente con filtros, paginado.
     *
     * @param array{estado?: string, desde?: string, hasta?: string, texto?: string} $filtros
     * @return array{items: array, total: int, pagina: int, paginas: int, porPagina: int}
     */
    public static function listarPorPaciente(int $idPaciente, array $filtros, int $pagina = 1, int $porPagina = 10): array
    {
        $porPagina = max(1, min(100, $porPagina));
        $pagina = max(1, $pagina);

        [$where, $params] = self::condicionesPaciente($idPaciente, $filtros);
        $params['paciente'] = $idPaciente;

        $total = 0;
        $conteo = Database::raw(
            'SELECT COUNT(*) AS total
             FROM cita c
             JOIN ficha_agenda fa ON fa.id = c.id_ficha_agenda
             JOIN horario_atencion h ON h.id = fa.id_horario
             JOIN servicio_medico sm ON sm.id = h.id_servicio_medico
             JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
             JOIN persona mp ON mp.id = me.id_medico
             WHERE ' . $where,
            $params
        );

        if (is_array($conteo) && isset($conteo[0]['total'])) {
            $total = (int) $conteo[0]['total'];
        }

        $paginas = (int) max(1, ceil($total / $porPagina));
        $pagina = min($pagina, $paginas);
        $offset = ($pagina - 1) * $porPagina;

        $filas = Database::raw(
            self::SELECT_CITA . ' WHERE ' . $where
            . ' ORDER BY fa.fecha_hora_inicio DESC, c.id DESC'
            . ' LIMIT ' . $porPagina . ' OFFSET ' . $offset,
            $params
        );

        return [
            'items' => self::normalizarFilas($filas),
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
            'porPagina' => $porPagina,
        ];
    }

    /** Detalle completo de una cita (modal de detalle). */
    public static function detalle(int $id): ?array
    {
        $filas = Database::raw(self::SELECT_CITA . ' WHERE c.id = :id', ['id' => $id]);
        $items = self::normalizarFilas($filas);

        return $items[0] ?? null;
    }

    /** Búsqueda de una cita por su código (recepción por código). */
    public static function buscarPorCodigo(string $codigo): ?array
    {
        $codigo = trim($codigo);

        if ($codigo === '') {
            return null;
        }

        $filas = Database::raw(self::SELECT_CITA . ' WHERE c.codigo = :codigo', ['codigo' => $codigo]);
        $items = self::normalizarFilas($filas);

        return $items[0] ?? null;
    }

    /** Registro de recepción (check-in) de una cita, si ya existe. */
    public static function recepcionDe(int $idCita): ?array
    {
        $filas = Database::raw(
            'SELECT id, fecha_hora_llegada, id_usuario_recibe, estado
             FROM recepcion_cita
             WHERE id_cita = :cita',
            ['cita' => $idCita]
        );

        if (!is_array($filas) || $filas === [] || !is_array($filas[0])) {
            return null;
        }

        return $filas[0];
    }

    /**
     * Detecta si el paciente ya tiene una cita activa que se solape con
     * el rango indicado (evita duplicidad entre distintos médicos).
     */
    public static function solapadaPaciente(int $idPaciente, string $inicio, string $fin): bool
    {
        $filas = Database::raw(
            "SELECT COUNT(*) AS total
             FROM cita c
             JOIN ficha_agenda fa ON fa.id = c.id_ficha_agenda
             WHERE c.id_paciente = :paciente
               AND c.estado NOT IN ('CANCELADA', 'INASISTENCIA', 'REPROGRAMADA')
               AND fa.fecha_hora_inicio < :fin
               AND fa.fecha_hora_fin > :inicio",
            [
                'paciente' => $idPaciente,
                'inicio' => $inicio,
                'fin' => $fin,
            ]
        );

        return (int) ($filas[0]['total'] ?? 0) > 0;
    }

    /**
     * Reserva una ficha para un paciente dentro de una transacción.
     *
     * Esta es la operación delicada del módulo: el candado FOR UPDATE
     * sobre la ficha serializa los intentos simultáneos y las
     * comprobaciones (ficha libre, horario vigente, servicio activo,
     * turno futuro y ausencia de solape del paciente) impiden la
     * duplicidad entre pacientes en el mismo horario y fecha.
     *
     * @throws \RuntimeException Con el mensaje apto para el usuario.
     */
    public static function reservar(int $idFicha, int $idPaciente, int $idRegistrador, string $motivo): int
    {
        return (int) Database::transaction(static function () use (
            $idFicha,
            $idPaciente,
            $idRegistrador,
            $motivo
        ): int {
            // es correcto
            $existePaciente = Database::raw(
                'SELECT COUNT(*) AS total FROM paciente WHERE id_persona = :id',
                ['id' => $idPaciente]
            );

            if ((int) ($existePaciente[0]['total'] ?? 0) === 0) {
                throw new \RuntimeException('El paciente seleccionado no existe.');
            }

            $ficha = FichaAgenda::bloquearParaReserva($idFicha);

            if ($ficha === null) {
                throw new \RuntimeException('El turno seleccionado no existe.');
            }

            if ((string) $ficha['estado'] !== 'ABIERTA') {
                throw new \RuntimeException('El turno seleccionado ya fue reservado por otro paciente.');
            }

            if (!(bool) $ficha['vigente'] || !(bool) $ficha['horario_estado']) {
                throw new \RuntimeException('El horario de ese turno ya no está vigente.');
            }

            if ((string) $ficha['servicio_estado'] !== 'ACTIVO') {
                throw new \RuntimeException('El servicio médico no está disponible para nuevas citas.');
            }

            if (!(bool) $ficha['futuro']) {
                throw new \RuntimeException('El turno seleccionado ya pasó.');
            }

            // verifica si ese paciente contaba anteriormente con esa fecha y hora el paciente
            if (self::solapadaPaciente($idPaciente, (string) $ficha['fecha_hora_inicio'], (string) $ficha['fecha_hora_fin'])) {
                throw new \RuntimeException('Ya tiene una cita que se solapa con ese horario.');
            }

            $cita = [
                'codigo' => self::nuevoCodigo((int) $ficha['id_especialidad']),
                'id_paciente' => $idPaciente,
                'id_ficha_agenda' => (int) $ficha['id'],
                'estado' => 'PROGRAMADA',
                'motivo_consulta' => $motivo,
                'id_usuario_registro' => $idRegistrador,
            ];

            $idCita = (int) self::query()->insert($cita);

            FichaAgenda::query()->update(['estado' => 'CERRADA'], ['id' => (int) $ficha['id']]);

            return $idCita;
        });
    }

    /** Código nuevo de cita: prefijo de especialidad + '_' + 5 dígitos aleatorios. */
    public static function nuevoCodigo(int $idEspecialidad): string
    {
        $filas = Database::raw( 'SELECT nombre FROM especialidad WHERE id = :id',
                ['id' => $idEspecialidad]
            );
        $nombre = strtoupper(trim($filas[0]['nombre']));
        $prefijo = substr($nombre, 0, 3);
        return $prefijo . '_' . random_int(10000, 99999);
    }

    /**
     * Estadísticas del día de la recepción: citas cuya ficha cae hoy.
     *
     * Pendientes = PROGRAMADA + CONFIRMADA + REPROGRAMADA (citados que aún
     * no llegan); En clínica = EN_RECEPCION + EN_ATENCION (ya están en el
     * centro). INASISTENCIA no tiene tarjeta, por lo que la suma de las
     * tarjetas de estados puede quedar por debajo del total.
     *
     * @return array{
     *     total: int, pendientes: int, en_clinica: int, atendidas: int,
     *     canceladas: int
     * }
     */
    public static function estadisticasDia(): array
    {
        $estadisticas = [
            'total' => 0,
            'pendientes' => 0,
            'en_clinica' => 0,
            'atendidas' => 0,
            'canceladas' => 0,
        ];

        $filas = Database::raw(
            'SELECT COUNT(*) AS total,
                    COUNT(CASE WHEN c.estado IN (\'PROGRAMADA\', \'CONFIRMADA\', \'REPROGRAMADA\') THEN 1 END) AS pendientes,
                    COUNT(CASE WHEN c.estado IN (\'EN_RECEPCION\', \'EN_ATENCION\') THEN 1 END) AS en_clinica,
                    COUNT(CASE WHEN c.estado = \'ATENDIDA\' THEN 1 END) AS atendidas,
                    COUNT(CASE WHEN c.estado = \'CANCELADA\' THEN 1 END) AS canceladas
             FROM cita c
             JOIN ficha_agenda fa ON fa.id = c.id_ficha_agenda
             WHERE fa.fecha = CURRENT_DATE'
        );

        if (is_array($filas) && isset($filas[0]) && is_array($filas[0])) {
            foreach (array_keys($estadisticas) as $clave) {
                $estadisticas[$clave] = (int) ($filas[0][$clave] ?? 0);
            }
        }

        return $estadisticas;
    }

    /**
     * Citas del día (ficha de hoy) para la tabla de recepción, con filtros.
     *
     * @param array{estado?: string, texto?: string} $filtros
     * @return array{items: array, total: int, pagina: int, paginas: int, porPagina: int}
     */
    public static function listarHoy(array $filtros, int $pagina = 1, int $porPagina = 10): array
    {
        $porPagina = max(1, min(100, $porPagina));
        $pagina = max(1, $pagina);

        $donde = ['fa.fecha = CURRENT_DATE'];
        $params = [];

        $estado = trim((string) ($filtros['estado'] ?? ''));
        $estadosValidos = array_merge(self::ESTADOS_CANCELABLES, ['EN_RECEPCION', 'EN_ATENCION', 'ATENDIDA', 'CANCELADA', 'INASISTENCIA']);
        if ($estado !== '' && in_array($estado, $estadosValidos, true)) {
            $donde[] = 'c.estado = :estado';
            $params['estado'] = $estado;
        }

        $texto = trim((string) ($filtros['texto'] ?? ''));
        if ($texto !== '') {
            $donde[] = "((p.nombre || ' ' || p.apellido) ILIKE :texto
                         OR p.ci ILIKE :texto OR c.codigo ILIKE :texto)";
            $params['texto'] = '%' . $texto . '%';
        }

        $where = implode(' AND ', $donde);

        $from = 'FROM cita c
                 JOIN ficha_agenda fa ON fa.id = c.id_ficha_agenda
                 JOIN paciente pa ON pa.id_persona = c.id_paciente
                 JOIN persona p ON p.id = pa.id_persona
                 JOIN horario_atencion ha ON ha.id = fa.id_horario
                 JOIN servicio_medico sm ON sm.id = ha.id_servicio_medico
                 JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
                 JOIN especialidad e ON e.id = me.id_especialidad
                 JOIN medico m ON m.id_persona = me.id_medico
                 JOIN persona pm ON pm.id = m.id_persona
                 LEFT JOIN recepcion_cita rc ON rc.id_cita = c.id';

        $total = 0;
        $conteo = Database::raw(
            "SELECT COUNT(DISTINCT c.id) AS total {$from} WHERE {$where}",
            $params
        );
        if (is_array($conteo) && isset($conteo[0]['total'])) {
            $total = (int) $conteo[0]['total'];
        }

        $paginas = (int) max(1, ceil($total / $porPagina));
        $pagina = min($pagina, $paginas);
        $offset = ($pagina - 1) * $porPagina;

        $filas = Database::raw(
            "SELECT c.id,
                    c.codigo,
                    c.estado,
                    fa.fecha_hora_inicio,
                    fa.fecha_hora_fin,
                    (p.nombre || ' ' || p.apellido) AS paciente,
                    p.ci AS paciente_ci,
                    (pm.nombre || ' ' || pm.apellido) AS medico,
                    e.nombre AS especialidad,
                    rc.id AS id_recepcion
             {$from}
             WHERE {$where}
             ORDER BY fa.fecha_hora_inicio, c.id
             LIMIT {$porPagina} OFFSET {$offset}",
            $params
        );

        return [
            'items' => self::normalizarFilas($filas),
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
            'porPagina' => $porPagina,
        ];
    }

    /**
     * Citas afectadas por un bloqueo de agenda: fichas CERRADAS dentro del
     * rango del bloqueo, de ese servicio médico, con cita reprogramable.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function afectadasPorBloqueo(int $idServicio, string $inicio, string $fin): array
    {
        $filas = Database::raw(
            'SELECT c.id,
                    c.codigo,
                    c.estado,
                    c.motivo_consulta,
                    fa.id AS id_ficha,
                    fa.fecha_hora_inicio,
                    fa.fecha_hora_fin,
                    (p.nombre || \' \' || p.apellido) AS paciente,
                    p.ci AS paciente_ci
             FROM cita c
             JOIN ficha_agenda fa ON fa.id = c.id_ficha_agenda
             JOIN horario_atencion h ON h.id = fa.id_horario
             JOIN paciente pac ON pac.id_persona = c.id_paciente
             JOIN persona p ON p.id = pac.id_persona
             WHERE h.id_servicio_medico = :servicio
               AND fa.estado = \'CERRADA\'
               AND fa.fecha_hora_inicio >= :inicio
               AND fa.fecha_hora_inicio < :fin
               AND c.estado IN (\'PROGRAMADA\',  \'REPROGRAMADA\')
             ORDER BY fa.fecha_hora_inicio',
            [
                'servicio' => $idServicio,
                'inicio' => $inicio,
                'fin' => $fin,
            ]
        );

        return self::normalizarFilas($filas);
    }

    /** @param array<string, mixed> $filtros */
    private static function condicionesPaciente(int $idPaciente, array $filtros): array
    {
        $donde = ['c.id_paciente = :paciente'];
        $params = [];

        $estado = trim((string) ($filtros['estado'] ?? ''));

        if ($estado !== '' && in_array($estado, [
            'PROGRAMADA', 'CONFIRMADA', 'REPROGRAMADA', 'EN_RECEPCION',
            'EN_ATENCION', 'ATENDIDA', 'CANCELADA', 'INASISTENCIA',
        ], true)) {
            $donde[] = 'c.estado = :estado_filtro';
            $params['estado_filtro'] = $estado;
        }

        $desde = trim((string) ($filtros['desde'] ?? ''));
        $hasta = trim((string) ($filtros['hasta'] ?? ''));

        if ($desde !== '') {
            $donde[] = 'fa.fecha >= :desde';
            $params['desde'] = $desde;
        }

        if ($hasta !== '') {
            $donde[] = 'fa.fecha <= :hasta';
            $params['hasta'] = $hasta;
        }

        $texto = trim((string) ($filtros['texto'] ?? ''));

        if ($texto !== '') {
            $donde[] = '(c.codigo ILIKE :texto OR c.motivo_consulta ILIKE :texto
                         OR (mp.nombre || \' \' || mp.apellido) ILIKE :texto)';
            $params['texto'] = '%' . $texto . '%';
        }

        return [implode(' AND ', $donde), $params];
    }

    /**
     * Normaliza las filas del SELECT base de citas.
     *
     * @param mixed $filas
     * @return array<int, array<string, mixed>>
     */
    private static function normalizarFilas(mixed $filas): array
    {
        $items = [];

        foreach ((array) $filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $items[] = [
                'id' => (int) ($fila['id'] ?? 0),
                'codigo' => (string) ($fila['codigo'] ?? ''),
                'id_paciente' => (int) ($fila['id_paciente'] ?? 0),
                'id_ficha_agenda' => (int) ($fila['id_ficha_agenda'] ?? 0),
                'estado' => (string) ($fila['estado'] ?? ''),
                'motivo_consulta' => (string) ($fila['motivo_consulta'] ?? ''),
                'id_usuario_registro' => (int) ($fila['id_usuario_registro'] ?? 0),
                'fecha_registro' => (string) ($fila['fecha_registro'] ?? ''),
                'observaciones' => (string) ($fila['observaciones'] ?? ''),
                'fecha' => (string) ($fila['fecha'] ?? ''),
                'fecha_hora_inicio' => (string) ($fila['fecha_hora_inicio'] ?? ''),
                'fecha_hora_fin' => (string) ($fila['fecha_hora_fin'] ?? ''),
                'ficha_estado' => (string) ($fila['ficha_estado'] ?? ''),
                'id_servicio_medico' => (int) ($fila['id_servicio_medico'] ?? 0),
                'id_especialidad' => (int) ($fila['id_especialidad'] ?? 0),
                'id_medico' => (int) ($fila['id_medico'] ?? 0),
                'id_ficha' => (int) ($fila['id_ficha'] ?? $fila['id_ficha_agenda'] ?? 0),
                'paciente' => (string) ($fila['paciente'] ?? ''),
                'paciente_ci' => (string) ($fila['paciente_ci'] ?? ''),
                'medico' => (string) ($fila['medico'] ?? ''),
                'especialidad' => (string) ($fila['especialidad'] ?? ''),
                'nro_colegiado' => (string) ($fila['nro_colegiado'] ?? ''),
                'id_recepcion' => isset($fila['id_recepcion']) ? (int) $fila['id_recepcion'] : null,
                'fecha_hora_llegada' => (string) ($fila['fecha_hora_llegada'] ?? ''),
            ];
        }

        return $items;
    }
}
