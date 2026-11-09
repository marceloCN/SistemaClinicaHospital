<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Database;
use Lib\Core\Model;

class HorarioAtencion extends Model
{
    protected string $table = 'horario_atencion';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_servicio_medico',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'duracion_ficha',
        'fecha_desde',
        'fecha_hasta',
        'estado',
    ];

    protected array $casts = [
        'estado' => 'boolean',
    ];

    protected bool $timestamps = false;

    /**
     * Listado paginado de horarios vigentes con datos del médico y especialidad.
     *
     * Los horarios vencidos (fecha_hasta < hoy) no se listan: sus fichas
     * se consultan en el reporte de fichas históricas.
     *
     * @param array{medico?: string, especialidad?: string, estado?: string} $filtros
     * @return array{items: array, total: int, pagina: int, paginas: int, porPagina: int}
     */
    public static function listar(array $filtros, int $pagina = 1, int $porPagina = 10): array
    {
        $porPagina = max(1, min(100, $porPagina));
        $pagina = max(1, $pagina);

        [$where, $params] = self::condiciones($filtros);

        $total = 0;
        $conteo = Database::raw(
            'SELECT COUNT(*) AS total
             FROM horario_atencion h
             JOIN servicio_medico sm ON sm.id = h.id_servicio_medico
             JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
             JOIN medico m ON m.id_persona = me.id_medico
             JOIN persona p ON p.id = m.id_persona
             JOIN especialidad e ON e.id = me.id_especialidad
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
            self::SELECT_HORARIO . ' WHERE ' . $where
            . ' ORDER BY p.apellido, p.nombre, e.nombre, h.dia_semana, h.hora_inicio'
            . ' LIMIT ' . $porPagina . ' OFFSET ' . $offset,
            $params
        );

        return [
            'items' => self::normalizar((array) $filas),
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
            'porPagina' => $porPagina,
        ];
    }

    /**
     * Todos los horarios vigentes del listado (sin paginación): mismos
     * filtros y orden que listar, para los reportes PDF y CSV.
     *
     * @param array{medico?: string, especialidad?: string, estado?: string} $filtros
     * @return array<int, array<string, mixed>>
     */
    public static function listarTodos(array $filtros): array
    {
        [$where, $params] = self::condiciones($filtros);

        $filas = Database::raw(
            self::SELECT_HORARIO . ' WHERE ' . $where
            . ' ORDER BY p.apellido, p.nombre, e.nombre, h.dia_semana, h.hora_inicio',
            $params
        );

        return self::normalizar((array) $filas);
    }

    /**
     * Detalle de un horario con médico y especialidad.
     */
    public static function detalle(int $id): ?array
    {
        $filas = Database::raw(
            self::SELECT_HORARIO . ' WHERE h.id = :id',
            ['id' => $id]
        );

        $items = self::normalizar((array) $filas);

        return $items[0] ?? null;
    }

    /**
     * Cuenta las fichas del horario que NO están en estado ABIERTA.
     *
     * Una ficha cerrada u ocupada (estado distinto de ABIERTA) indica que
     * su cita está programada o atendida; esa cantidad bloquea la edición
     * y la desactivación del horario.
     *
     * @return int Cantidad de fichas cerradas/ocupadas del horario.
     */
    public static function tieneFichasConCitas(int $id): int
    {
        $filas = Database::raw(
            "SELECT COUNT(fa.*) AS total from horario_atencion ha, ficha_agenda fa
             where ha.id = fa.id_horario and ha.id = :id
             and fa.estado is distinct from 'ABIERTA'",
            ['id' => $id]
        );

        return (int) ($filas[0]['total']) ?? 0;
    }
    /**
     * Indica si el horario tiene ficha generada (estado = 'ABIERTA')
    */
    public static function tieneFichasGeneradas(int $id): int
    {
        $filas = Database::raw(
            "SELECT COUNT(fa.*) AS total from horario_atencion ha, ficha_agenda fa
             where ha.id = fa.id_horario and ha.id = :id
             and fa.estado = 'ABIERTA'",
            ['id' => $id]
        );

        return (int) ($filas[0]['total']) ?? 0;
    }

    /**
     * Devuelve conteos de fichas por horario.
     *
     * Clasifica por el estado de la misma ficha (sin unir con cita):
     * libres = estado ABIERTA; cerradas = cualquier otro estado.
     *
     * @param int[] $idsHorarios
     * @return array<int, array{total_fichas: int, cerradas: int, libres: int}>
     */
    public static function conteosPorHorario(array $idsHorarios): array
    {
        $resultado = [];

        if ($idsHorarios === []) {
            return $resultado;
        }

        $idsHorarios = array_filter(array_map('intval', $idsHorarios), static fn (int $id): bool => $id > 0);

        if ($idsHorarios === []) {
            return $resultado;
        }

        $marcadores = implode(',', array_fill(0, count($idsHorarios), '?'));
        $params = array_values($idsHorarios);

        $filas = Database::raw(
            "SELECT id_horario,
                    COUNT(*) AS total_fichas,
                    COUNT(CASE WHEN estado IS DISTINCT FROM 'ABIERTA' THEN 1 END) AS cerradas,
                    COUNT(CASE WHEN estado = 'ABIERTA' THEN 1 END) AS libres
             FROM ficha_agenda
             WHERE id_horario IN ({$marcadores})
             GROUP BY id_horario",
            $params
        );

        foreach ((array) $filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $idHorario = (int) ($fila['id_horario'] ?? 0);

            if ($idHorario <= 0) {
                continue;
            }

            $resultado[$idHorario] = [
                'total_fichas' => (int) ($fila['total_fichas'] ?? 0),
                'cerradas' => (int) ($fila['cerradas'] ?? 0),
                'libres' => (int) ($fila['libres'] ?? 0),
            ];
        }

        return $resultado;
    }

    /**
     * Detecta si un nuevo horario se solapa con uno activo y vigente del
     * mismo servicio médico y día de la semana.
     *
     * Los horarios vencidos (fecha_hasta < hoy) ya no se gestionan en el
     * módulo, por lo que no bloquean la creación de nuevos horarios.
     */
    public static function solapado(
        int $idServicio,
        int $dia,
        string $horaInicio,
        string $horaFin,
        ?int $excluirId = null
    ): bool {
        $sql = "SELECT COUNT(*) AS total
                FROM horario_atencion
                WHERE id_servicio_medico = :servicio
                  AND dia_semana = :dia
                  AND estado = TRUE
                  AND (fecha_hasta IS NULL OR fecha_hasta >= CURRENT_DATE)
                  AND hora_inicio < :fin
                  AND hora_fin > :inicio";

        $params = [
            'servicio' => $idServicio,
            'dia' => $dia,
            'inicio' => $horaInicio,
            'fin' => $horaFin,
        ];

        if ($excluirId !== null && $excluirId > 0) {
            $sql .= ' AND id <> :excluir';
            $params['excluir'] = $excluirId;
        }

        $filas = Database::raw($sql, $params);

        return (int) ($filas[0]['total'] ?? 0) > 0;
    }

    /**
     * Conteos globales del módulo de horarios.
     *
     * Solo cuenta los horarios vigentes (fecha_hasta >= hoy o sin fecha
     * fin): los vencidos pasan al reporte de fichas históricas y dejan de
     * verse en el módulo.
     *
     * @return array{total: int, activos: int, inactivos: int, medicos: int, fichas: int}
     */
    public static function estadisticas(): array
    {
        $estadisticas = [
            'total' => 0,
            'activos' => 0,
            'inactivos' => 0,
            'medicos' => 0,
            'fichas' => 0,
        ];

        $vigente = '(h.fecha_hasta IS NULL OR h.fecha_hasta >= CURRENT_DATE)';

        $filas = Database::raw(
            "SELECT (SELECT COUNT(*) FROM horario_atencion h WHERE {$vigente}) AS total,
                    (SELECT COUNT(*) FROM horario_atencion h WHERE {$vigente} AND h.estado = TRUE) AS activos,
                    (SELECT COUNT(*) FROM horario_atencion h WHERE {$vigente} AND h.estado = FALSE) AS inactivos,
                    (SELECT COUNT(DISTINCT me.id_medico)
                     FROM horario_atencion h
                     JOIN servicio_medico sm ON sm.id = h.id_servicio_medico
                     JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
                     WHERE {$vigente}) AS medicos,
                    (SELECT COUNT(*)
                     FROM ficha_agenda fa
                     JOIN horario_atencion h ON h.id = fa.id_horario
                     WHERE {$vigente}) AS fichas"
        );

        if (is_array($filas) && isset($filas[0]) && is_array($filas[0])) {
            foreach (array_keys($estadisticas) as $clave) {
                $estadisticas[$clave] = (int) ($filas[0][$clave] ?? 0);
            }
        }

        return $estadisticas;
    }

    /** Condiciones WHERE compartidas por listar y detalle. */
    private static function condiciones(array $filtros): array
    {
        $medico = trim((string) ($filtros['medico'] ?? ''));
        $especialidad = (string) ($filtros['especialidad'] ?? '');
        $estado = (string) ($filtros['estado'] ?? '');

        $donde = ['(h.fecha_hasta IS NULL OR h.fecha_hasta >= CURRENT_DATE)'];
        $params = [];

        if ($medico !== '') {
            $donde[] = "((p.nombre || ' ' || p.apellido) ILIKE :medico
                         OR p.ci ILIKE :medico OR m.nro_colegiado ILIKE :medico)";
            $params['medico'] = '%' . $medico . '%';
        }

        if ($especialidad !== '' && (int) $especialidad > 0) {
            $donde[] = 'e.id = :especialidad';
            $params['especialidad'] = (int) $especialidad;
        }

        if ($estado === '1') {
            $donde[] = 'h.estado = TRUE';
        } elseif ($estado === '0') {
            $donde[] = 'h.estado = FALSE';
        }

        return [implode(' AND ', $donde), $params];
    }

    /** Normaliza las filas del SELECT de horarios. */
    private static function normalizar(array $filas): array
    {
        $items = [];
        $dias = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];

        foreach ($filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $items[] = [
                'id' => (int) ($fila['id'] ?? 0),
                'id_servicio_medico' => (int) ($fila['id_servicio_medico'] ?? 0),
                'id_medico' => (int) ($fila['id_medico'] ?? 0),
                'id_especialidad' => (int) ($fila['id_especialidad'] ?? 0),
                'nombre_completo' => (string) ($fila['nombre_completo'] ?? ''),
                'ci' => (string) ($fila['ci'] ?? ''),
                'nro_colegiado' => (string) ($fila['nro_colegiado'] ?? ''),
                'especialidad' => (string) ($fila['especialidad'] ?? ''),
                'dia_semana' => (int) ($fila['dia_semana'] ?? 0),
                'dia_nombre' => $dias[(int) ($fila['dia_semana'] ?? 0)] ?? '—',
                'hora_inicio' => (string) ($fila['hora_inicio'] ?? ''),
                'hora_fin' => (string) ($fila['hora_fin'] ?? ''),
                'duracion_ficha' => (int) ($fila['duracion_ficha'] ?? 0),
                'fecha_desde' => (string) ($fila['fecha_desde'] ?? ''),
                'fecha_hasta' => (string) ($fila['fecha_hasta'] ?? ''),
                'estado' => (bool) ($fila['estado'] ?? false),
            ];
        }

        return $items;
    }

    /** SELECT base con médico y especialidad. */
    private const SELECT_HORARIO = "
        SELECT
            h.id,
            h.id_servicio_medico,
            h.dia_semana,
            h.hora_inicio,
            h.hora_fin,
            h.duracion_ficha,
            h.fecha_desde,
            h.fecha_hasta,
            h.estado,
            me.id_medico,
            me.id_especialidad,
            m.nro_colegiado,
            p.nombre,
            p.apellido,
            (p.nombre || ' ' || p.apellido) AS nombre_completo,
            p.ci,
            e.nombre AS especialidad
        FROM horario_atencion h
        JOIN servicio_medico sm ON sm.id = h.id_servicio_medico
        JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
        JOIN medico m ON m.id_persona = me.id_medico
        JOIN persona p ON p.id = m.id_persona
        JOIN especialidad e ON e.id = me.id_especialidad
    ";
}
