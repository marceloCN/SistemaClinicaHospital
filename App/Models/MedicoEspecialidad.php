<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Database;
use Lib\Core\Model;

/**
 * Acreditación de un médico en una especialidad (medico_especialidad).
 *
 * Es el paso previo a servicio_medico: mientras un médico no tenga la
 * especialidad asignada en esta tabla no puede sumarse al servicio
 * (y por lo tanto no puede recibir horarios de atención ni fichas).
 */
class MedicoEspecialidad extends Model
{
    protected string $table = 'medico_especialidad';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_medico',
        'id_especialidad',
        'institucion_formadora',
        'nro_certificado',
        'fecha_obtencion',
        'estado',
    ];

    protected array $casts = [
        'estado' => 'boolean',
    ];

    protected bool $timestamps = false;

    /* ------------------------------------------------------------------
     * Pestaña Médicos: control de las especialidades de cada médico
     * ---------------------------------------------------------------- */

    /**
     * Médicos con sus especialidades asignadas, filtrados y paginados.
     *
     * @param array{medico?: string, acreditaciones?: string} $filtros
     * @return array{items: array, total: int, pagina: int, paginas: int, porPagina: int}
     */
    public static function listarMedicos(array $filtros, int $pagina = 1, int $porPagina = 10): array
    {
        $porPagina = max(1, min(100, $porPagina));
        $pagina = max(1, $pagina);

        $consulta = self::consultaMedicos($filtros);
        $nucleo = $consulta['sql'];
        $params = $consulta['params'];

        $total = 0;
        $conteo = Database::raw('SELECT COUNT(*) AS total FROM (' . $nucleo . ') x', $params);

        if (is_array($conteo) && isset($conteo[0]['total'])) {
            $total = (int) $conteo[0]['total'];
        }

        $paginas = (int) max(1, ceil($total / $porPagina));
        $pagina = min($pagina, $paginas);
        $offset = ($pagina - 1) * $porPagina;

        $filas = Database::raw(
            'SELECT * FROM (' . $nucleo . ') x'
            . ' ORDER BY x.apellido, x.nombre'
            . ' LIMIT ' . $porPagina . ' OFFSET ' . $offset,
            $params
        );

        $items = self::prepararMedicos($filas);

        return [
            'items' => $items,
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
            'porPagina' => $porPagina,
        ];
    }

    /**
     * Todos los médicos del listado (sin paginación): mismos filtros y
     * orden que listarMedicos, para los reportes PDF y CSV.
     *
     * @param array{medico?: string, acreditaciones?: string} $filtros
     * @return array<int, array<string, mixed>>
     */
    public static function listarMedicosTodos(array $filtros): array
    {
        $consulta = self::consultaMedicos($filtros);

        $filas = Database::raw(
            'SELECT * FROM (' . $consulta['sql'] . ') x ORDER BY x.apellido, x.nombre',
            $consulta['params']
        );

        return self::prepararMedicos($filas);
    }

    /**
     * Construye la consulta base de la pestaña Médicos: filtros por texto
     * (nombre/CI/colegiado) y con/sin acreditaciones, con los conteos de
     * especialidades y servicios.
     *
     * @param array{medico?: string, acreditaciones?: string} $filtros
     * @return array{sql: string, params: array<string, string>}
     */
    private static function consultaMedicos(array $filtros): array
    {
        $texto = trim((string) ($filtros['medico'] ?? ''));
        $acreditaciones = (string) ($filtros['acreditaciones'] ?? '');

        $condiciones = ['m.estado = TRUE', 'p.estado = TRUE'];
        $params = [];

        if ($texto !== '') {
            $condiciones[] = "((p.nombre || ' ' || p.apellido) ILIKE :nom
                              OR p.ci ILIKE :ci OR m.nro_colegiado ILIKE :col)";
            $params['nom'] = '%' . $texto . '%';
            $params['ci'] = '%' . $texto . '%';
            $params['col'] = '%' . $texto . '%';
        }

        $having = '';

        if ($acreditaciones === 'con') {
            $having = ' HAVING COUNT(DISTINCT me.id) > 0';
        } elseif ($acreditaciones === 'sin') {
            $having = ' HAVING COUNT(DISTINCT me.id) = 0';
        }

        $sql = 'SELECT m.id_persona AS id,
                       p.ci,
                       p.nombre,
                       p.apellido,
                       (p.nombre || \' \' || p.apellido) AS nombre_completo,
                       m.nro_colegiado,
                       COUNT(DISTINCT me.id) AS total_especialidades,
                       COUNT(DISTINCT sm.id) AS total_servicios,
                       COUNT(DISTINCT CASE WHEN sm.estado = \'ACTIVO\' THEN sm.id END) AS servicios_activos
                FROM medico m
                JOIN persona p ON p.id = m.id_persona
                LEFT JOIN medico_especialidad me ON me.id_medico = m.id_persona
                LEFT JOIN servicio_medico sm ON sm.id_medico_especialidad = me.id
                WHERE ' . implode(' AND ', $condiciones) . '
                GROUP BY m.id_persona, p.ci, p.nombre, p.apellido, m.nro_colegiado'
            . $having;

        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * Normaliza las filas del listado de médicos y adjunta sus
     * acreditaciones (especialidades con servicios).
     *
     * @param mixed $filas
     * @return array<int, array<string, mixed>>
     */
    private static function prepararMedicos(mixed $filas): array
    {
        $items = [];

        foreach ((array) $filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $fila['id'] = (int) ($fila['id'] ?? 0);
            $fila['total_especialidades'] = (int) ($fila['total_especialidades'] ?? 0);
            $fila['total_servicios'] = (int) ($fila['total_servicios'] ?? 0);
            $fila['servicios_activos'] = (int) ($fila['servicios_activos'] ?? 0);
            $fila['especialidades'] = [];
            $items[] = $fila;
        }

        $ids = array_column($items, 'id');

        if ($ids !== []) {
            $porMedico = self::acreditacionesDe($ids);

            foreach ($items as $indice => $fila) {
                $items[$indice]['especialidades'] = $porMedico[$fila['id']] ?? [];
            }
        }

        return $items;
    }

    /**
     * Especialidades de los médicos indicados, con sus servicios.
     *
     * @param int[] $idsMedicos
     * @return array<int, array<int, array<string, mixed>>>
     */
    public static function acreditacionesDe(array $idsMedicos): array
    {
        $idsMedicos = array_values(array_unique(array_filter(array_map('intval', $idsMedicos))));

        if ($idsMedicos === []) {
            return [];
        }

        $placeholders = [];
        $params = [];

        foreach ($idsMedicos as $indice => $idMedico) {
            $clave = 'm' . $indice;
            $placeholders[] = ':' . $clave;
            $params[$clave] = $idMedico;
        }

        $filas = Database::raw(
            'SELECT me.id_medico, me.id_especialidad, e.nombre,
                    e.estado AS especialidad_estado,
                    me.estado AS acreditacion_estado,
                    COUNT(sm.id) AS total_servicios,
                    COUNT(CASE WHEN sm.estado = \'ACTIVO\' THEN 1 END) AS servicios_activos
             FROM medico_especialidad me
             JOIN especialidad e ON e.id = me.id_especialidad
             LEFT JOIN servicio_medico sm ON sm.id_medico_especialidad = me.id
             WHERE me.id_medico IN (' . implode(', ', $placeholders) . ')
             GROUP BY me.id_medico, me.id_especialidad, e.nombre, e.estado, me.estado
             ORDER BY e.nombre',
            $params
        );

        $mapa = [];

        foreach ((array) $filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $idMedico = (int) ($fila['id_medico'] ?? 0);

            $mapa[$idMedico][] = [
                'id_especialidad' => (int) ($fila['id_especialidad'] ?? 0),
                'nombre' => (string) ($fila['nombre'] ?? ''),
                'especialidad_activa' => (bool) ($fila['especialidad_estado'] ?? false),
                'total_servicios' => (int) ($fila['total_servicios'] ?? 0),
                'servicios_activos' => (int) ($fila['servicios_activos'] ?? 0),
            ];
        }

        return $mapa;
    }

    /**
     * Especialidades asignadas agrupadas por médico (solo acreditaciones
     * vigentes y especialidades activas): se usa en Servicio médico para
     * que solo pueda elegirse lo ya acreditado en la pestaña Médicos.
     *
     * @return array<int, array<int, string>>
     */
    public static function acreditacionesPorMedico(): array
    {
        $filas = Database::raw(
            'SELECT me.id_medico, me.id_especialidad, e.nombre
             FROM medico_especialidad me
             JOIN especialidad e ON e.id = me.id_especialidad
             WHERE me.estado = TRUE AND e.estado = TRUE
             ORDER BY e.nombre'
        );

        $mapa = [];

        foreach ((array) $filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $mapa[(int) ($fila['id_medico'] ?? 0)][] = [
                'id' => (int) ($fila['id_especialidad'] ?? 0),
                'nombre' => (string) ($fila['nombre'] ?? ''),
            ];
        }

        return $mapa;
    }

    /** Ficha del médico (con alta vigente) o null si no existe. */
    public static function medico(int $id): ?array
    {
        $filas = Database::raw(
            "SELECT m.id_persona AS id,
                    (p.nombre || ' ' || p.apellido) AS nombre_completo,
                    p.ci,
                    m.nro_colegiado
             FROM medico m
             JOIN persona p ON p.id = m.id_persona
             WHERE m.id_persona = :id AND m.estado = TRUE AND p.estado = TRUE",
            ['id' => $id]
        );

        if (!is_array($filas) || $filas === [] || !is_array($filas[0])) {
            return null;
        }

        return $filas[0];
    }

    /** ¿Ya está asignada la especialidad al médico? */
    public static function existe(int $idMedico, int $idEspecialidad): bool
    {        $filas = Database::raw(
            'SELECT COUNT(*) AS total FROM medico_especialidad
             WHERE id_medico = :medico AND id_especialidad = :especialidad',
            ['medico' => $idMedico, 'especialidad' => $idEspecialidad]
        );

        return (int) ($filas[0]['total'] ?? 0) > 0;
    }

    /**
     * Asigna una o varias especialidades al médico.
     *
     * Las que ya estaban asignadas se contabilizan como existentes.
     *
     * @param int[] $idsEspecialidades
     * @return array{creadas: int, existentes: int}
     */
    public static function asignar(int $idMedico, array $idsEspecialidades): array
    {
        $ids = [];

        foreach ($idsEspecialidades as $id) {
            $id = (int) $id;

            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        $creadas = 0;
        $existentes = 0;

        foreach ($ids as $idEspecialidad) {
            if (self::existe($idMedico, $idEspecialidad)) {
                $existentes++;
                continue;
            }

            try {
                $id = (int) self::query()->insert([
                    'id_medico' => $idMedico,
                    'id_especialidad' => $idEspecialidad,
                    'estado' => true,
                ]);

                if ($id > 0) {
                    $creadas++;
                }
            } catch (\Throwable $e) {
                // Índice único (id_medico, id_especialidad): otro proceso la creó.
                error_log('Acreditación duplicada: ' . $e->getMessage());
                $existentes++;
            }
        }

        return ['creadas' => $creadas, 'existentes' => $existentes];
    }

    /**
     * Servicios médicos que cuelgan de esa acreditación.
     *
     * Si hay al menos uno, la especialidad no puede quitarse: borrar la
     * acreditación en cascada borraría servicios, horarios y fichas.
     */
    public static function tieneServicios(int $idMedico, int $idEspecialidad): int
    {
        $filas = Database::raw(
            'SELECT COUNT(*) AS total
             FROM servicio_medico sm
             JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
             WHERE me.id_medico = :medico AND me.id_especialidad = :especialidad',
            ['medico' => $idMedico, 'especialidad' => $idEspecialidad]
        );

        return (int) ($filas[0]['total'] ?? 0);
    }

    /** Quita la especialidad al médico (solo si no tiene servicios). */
    public static function quitar(int $idMedico, int $idEspecialidad): bool
    {
        return self::query()->delete([
            'id_medico' => $idMedico,
            'id_especialidad' => $idEspecialidad,
        ]) > 0;
    }

}
