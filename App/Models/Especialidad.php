<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Database;
use Lib\Core\Model;

class Especialidad extends Model
{
    protected string $table = 'especialidad';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'nombre',
        'descripcion',
        'estado',
    ];

    protected array $casts = [
        'estado' => 'boolean',
    ];

    protected bool $timestamps = false;

    /**
     * Especialidades activas ordenadas alfabéticamente.
     */
    public static function activas(): array
    {
        return self::query()
            ->where('estado', true)
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Especialidades con servicio médico activo.
     *
     * Si todavía no existen registros en servicio_medico, se usan las
     * acreditaciones activas de medico_especialidad como respaldo.
     */
    public static function ofrecidas(): array
    {
        if (!ServicioMedico::hayRegistros()) {
            return self::ofrecidasDesdeAcreditaciones();
        }

        $sql = "
            SELECT
                e.id,
                e.nombre,
                e.descripcion,
                COUNT(DISTINCT me.id_medico) AS total_medicos
            FROM especialidad e
            JOIN medico_especialidad me
                ON me.id_especialidad = e.id
                AND me.estado = TRUE
            JOIN servicio_medico sm
                ON sm.id_medico_especialidad = me.id
                AND sm.estado = 'ACTIVO'
            JOIN medico m
                ON m.id_persona = me.id_medico
                AND m.estado = TRUE
            JOIN persona p
                ON p.id = m.id_persona
                AND p.estado = TRUE
            WHERE e.estado = TRUE
            GROUP BY e.id, e.nombre, e.descripcion
            ORDER BY e.nombre
        ";

        return Database::raw($sql);
    }

    /**
     * Respaldo cuando la tabla servicio_medico aún está vacía.
     */
    private static function ofrecidasDesdeAcreditaciones(): array
    {
        $sql = "
            SELECT
                e.id,
                e.nombre,
                e.descripcion,
                COUNT(DISTINCT CASE WHEN m.estado = TRUE AND p.estado = TRUE THEN me.id_medico END) AS total_medicos
            FROM especialidad e
            LEFT JOIN medico_especialidad me
                ON me.id_especialidad = e.id
                AND me.estado = TRUE
            LEFT JOIN medico m
                ON m.id_persona = me.id_medico
                AND m.estado = TRUE
            LEFT JOIN persona p
                ON p.id = m.id_persona
                AND p.estado = TRUE
            WHERE e.estado = TRUE
            GROUP BY e.id, e.nombre, e.descripcion
            ORDER BY e.nombre
        ";

        return Database::raw($sql);
    }

    /**
     * Icono de Font Awesome según el nombre de la especialidad.
     *
     * Se usa en la portada pública (directorio de especialidades).
     */
    public static function icono(string $nombre): string
    {
        return match (true) {
            stripos($nombre, 'cardiolog') !== false => 'fa-heartbeat',
            stripos($nombre, 'pediatr') !== false => 'fa-child',
            stripos($nombre, 'ginecolog') !== false => 'fa-female',
            stripos($nombre, 'odontolog') !== false || stripos($nombre, 'dental') !== false => 'fa-tooth',
            stripos($nombre, 'medicina general') !== false => 'fa-user-md',
            stripos($nombre, 'traumatolog') !== false || stripos($nombre, 'ortoped') !== false => 'fa-bone',
            stripos($nombre, 'dermatolog') !== false => 'fa-allergies',
            stripos($nombre, 'oftalmolog') !== false => 'fa-eye',
            stripos($nombre, 'nutricion') !== false => 'fa-apple-alt',
            stripos($nombre, 'laboratorio') !== false => 'fa-flask',
            stripos($nombre, 'radiolog') !== false || stripos($nombre, 'imagen') !== false => 'fa-x-ray',
            stripos($nombre, 'psicolog') !== false => 'fa-brain',
            stripos($nombre, 'neurolog') !== false => 'fa-brain',
            stripos($nombre, 'oncolog') !== false => 'fa-ribbon',
            stripos($nombre, 'urolog') !== false => 'fa-male',
            default => 'fa-stethoscope',
        };
    }

    /* ------------------------------------------------------------------
     * Gestor de especialidades (dashboard administrativo)
     * ---------------------------------------------------------------- */

    /** Conteos globales del módulo para la pestaña Resumen. */
    public static function estadisticas(): array
    {
        $estadisticas = [
            'total' => 0,
            'activas' => 0,
            'inactivas' => 0,
            'acreditaciones' => 0,
            'medicos' => 0,
            'servicios_activos' => 0,
            'bloqueos_activos' => 0,
        ];

        $filas = Database::raw(
            "SELECT (SELECT COUNT(*) FROM especialidad) AS total,
                    (SELECT COUNT(*) FROM especialidad WHERE estado) AS activas,
                    (SELECT COUNT(*) FROM especialidad WHERE NOT estado) AS inactivas,
                    (SELECT COUNT(*) FROM medico_especialidad WHERE estado) AS acreditaciones,
                    (SELECT COUNT(DISTINCT id_medico) FROM medico_especialidad WHERE estado) AS medicos,
                    (SELECT COUNT(*) FROM servicio_medico WHERE estado = 'ACTIVO') AS servicios_activos,
                    (SELECT COUNT(*) FROM bloqueo_agenda WHERE estado = 'ACTIVO') AS bloqueos_activos"
        );

        if (is_array($filas) && isset($filas[0]) && is_array($filas[0])) {
            foreach (array_keys($estadisticas) as $clave) {
                $estadisticas[$clave] = (int) ($filas[0][$clave] ?? 0);
            }
        }

        return $estadisticas;
    }

    /**
     * Especialidades con sus conteos de médicos y servicios, filtradas.
     *
     * @param array{nombre?: string, estado?: string} $filtros
     * @return array{items: array, total: int, pagina: int, paginas: int, porPagina: int}
     */
    public static function listar(array $filtros, int $pagina = 1, int $porPagina = 10): array
    {
        $porPagina = max(1, min(100, $porPagina));
        $pagina = max(1, $pagina);

        [$where, $params] = self::condiciones($filtros);

        $total = 0;
        $conteo = Database::raw(
            'SELECT COUNT(*) AS total FROM especialidad e WHERE ' . $where,
            $params
        );

        if (is_array($conteo) && isset($conteo[0]['total'])) {
            $total = (int) $conteo[0]['total'];
        }

        $paginas = (int) max(1, ceil($total / $porPagina));
        $pagina = min($pagina, $paginas);
        $offset = ($pagina - 1) * $porPagina;

        $filas = Database::raw(
            self::SELECT_ESPECIALIDAD . ' WHERE ' . $where
            . ' GROUP BY e.id, e.nombre, e.descripcion, e.estado'
            . ' ORDER BY e.nombre'
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
     * Todas las especialidades que coincidan con los filtros (PDF).
     *
     * @param array{nombre?: string, estado?: string} $filtros
     */
    public static function listarTodas(array $filtros): array
    {
        [$where, $params] = self::condiciones($filtros);

        $filas = Database::raw(
            self::SELECT_ESPECIALIDAD . ' WHERE ' . $where
            . ' GROUP BY e.id, e.nombre, e.descripcion, e.estado'
            . ' ORDER BY e.nombre',
            $params
        );

        return self::normalizar((array) $filas);
    }

    /** Especialidad por id con sus conteos (modal de edición). */
    public static function detalle(int $id): ?array
    {
        $filas = Database::raw(
            self::SELECT_ESPECIALIDAD . ' WHERE e.id = :id'
            . ' GROUP BY e.id, e.nombre, e.descripcion, e.estado',
            ['id' => $id]
        );

        $items = self::normalizar((array) $filas);

        return $items[0] ?? null;
    }

    /** Indica si el nombre de especialidad ya está en uso. */
    public static function nombreDisponible(string $nombre, ?int $excluirId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS total FROM especialidad e'
            . ' WHERE LOWER(e.nombre) = LOWER(:nombre)';
        $params = ['nombre' => $nombre];

        if ($excluirId !== null && $excluirId > 0) {
            $sql .= ' AND e.id <> :excluir';
            $params['excluir'] = $excluirId;
        }

        $filas = Database::raw($sql, $params);

        return (int) ($filas[0]['total'] ?? 0) === 0;
    }

    /**
     * Servicios médicos vinculados a la especialidad (cualquier estado).
     *
     * Mientras exista al menos uno, la especialidad no puede darse de baja:
     * de servicio_medico dependen los horarios de atención y las fichas.
     */
    public static function serviciosVinculados(int $id): int
    {
        $filas = Database::raw(
            'SELECT COUNT(*) AS total
             FROM servicio_medico sm
             JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
             WHERE me.id_especialidad = :id',
            ['id' => $id]
        );

        return (int) ($filas[0]['total'] ?? 0);
    }

    /** Servicios vinculados con estado ACTIVO (para mensajes de bloqueo). */
    public static function serviciosActivosDe(int $id): int
    {
        $filas = Database::raw(
            "SELECT COUNT(*) AS total
             FROM servicio_medico sm
             JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
             WHERE me.id_especialidad = :id AND sm.estado = 'ACTIVO'",
            ['id' => $id]
        );

        return (int) ($filas[0]['total'] ?? 0);
    }

    /** Top de especialidades por número de médicos acreditados (resumen). */
    public static function topMedicos(int $limite = 6): array
    {
        $limite = max(1, min(12, $limite));

        $filas = Database::raw(
            "SELECT e.id, e.nombre, e.estado,
                    COUNT(DISTINCT me.id_medico) AS total_medicos
             FROM especialidad e
             LEFT JOIN medico_especialidad me
                ON me.id_especialidad = e.id
                AND me.estado = TRUE
             GROUP BY e.id, e.nombre, e.estado
             ORDER BY total_medicos DESC, e.nombre
             LIMIT " . $limite
        );

        return (array) $filas;
    }

    /** Condiciones WHERE compartidas por listar / listarTodas / detalle. */
    private static function condiciones(array $filtros): array
    {
        $nombre = trim((string) ($filtros['nombre'] ?? ''));
        $estado = (string) ($filtros['estado'] ?? '');

        $donde = ['TRUE'];
        $params = [];

        if ($nombre !== '') {
            $donde[] = 'e.nombre ILIKE :nombre';
            $params['nombre'] = '%' . $nombre . '%';
        }

        if ($estado === '1') {
            $donde[] = 'e.estado = TRUE';
        } elseif ($estado === '0') {
            $donde[] = 'e.estado = FALSE';
        }

        return [implode(' AND ', $donde), $params];
    }

    /** Normaliza las filas del SELECT con conteos. */
    private static function normalizar(array $filas): array
    {
        $items = [];

        foreach ($filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $items[] = [
                'id' => (int) ($fila['id'] ?? 0),
                'nombre' => (string) ($fila['nombre'] ?? ''),
                'descripcion' => (string) ($fila['descripcion'] ?? ''),
                'estado' => (bool) ($fila['estado'] ?? false),
                'total_medicos' => (int) ($fila['total_medicos'] ?? 0),
                'servicios_activos' => (int) ($fila['servicios_activos'] ?? 0),
            ];
        }

        return $items;
    }

    /** SELECT base con los conteos de médicos y servicios activos. */
    private const SELECT_ESPECIALIDAD = "
        SELECT
            e.id,
            e.nombre,
            e.descripcion,
            e.estado,
            COUNT(DISTINCT me.id_medico) FILTER (WHERE me.estado) AS total_medicos,
            COUNT(DISTINCT sm.id) FILTER (WHERE sm.estado = 'ACTIVO') AS servicios_activos
        FROM especialidad e
        LEFT JOIN medico_especialidad me ON me.id_especialidad = e.id
        LEFT JOIN servicio_medico sm ON sm.id_medico_especialidad = me.id
    ";
}
