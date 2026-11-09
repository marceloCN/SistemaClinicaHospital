<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Database;
use Lib\Core\Model;

class ServicioMedico extends Model
{
    protected string $table = 'servicio_medico';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_medico_especialidad',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'observaciones',
    ];

    protected bool $timestamps = false;

    public static function hayRegistros(): bool
    {
        return self::query()->count() > 0;
    }

    public static function hayActivos(): bool
    {
        return self::query()->where('estado', 'ACTIVO')->count() > 0;
    }

    // me encuentra las especialidades medicas activas que estan asociadas a un medico activo
    public static function especialidadesActivas(): array
    {
        $filas = Database::raw(
            "SELECT e.id, e.nombre
             FROM servicio_medico sm, medico_especialidad me, especialidad e
             WHERE e.id = me.id_especialidad AND me.id = sm.id_medico_especialidad
             GROUP BY e.id, e.nombre
            ORDER BY e.id asc"
        );  
        return $filas ? : [];
    }

    // me encuentra los medicos activos que estan asociados a una especialidad con servicio medico activo
    public static function medicosPorEspecialidad(int $idEspecialidad): array
    {
        $filas = Database::raw(
            "SELECT sm.id, CONCAT_WS(' ', p.nombre, p.apellido) AS nombre_completo, m.nro_colegiado, p.ci
             FROM servicio_medico sm, medico_especialidad me,
             especialidad e, medico m, persona p
             WHERE e.id = :id_especialidad AND e.id = me.id_especialidad AND me.id_medico = m.id_persona
             AND p.id = m.id_persona AND me.id = sm.id_medico_especialidad AND sm.estado = 'ACTIVO'
             ORDER BY sm.id ASC;",
             ['id_especialidad' => $idEspecialidad]);
        return $filas ? : [];
    }


    /* ------------------------------------------------------------------
     * Gestor de especialidades: acreditaciones de los médicos
     * ---------------------------------------------------------------- */

    /**
     * Servicios médicos (médico + especialidad) con sus bloqueos activos.
     *
     * @param array{especialidad?: string, estado?: string, medico?: string} $filtros
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
             FROM servicio_medico sm
             JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
             JOIN especialidad e ON e.id = me.id_especialidad
             JOIN medico m ON m.id_persona = me.id_medico
             JOIN persona p ON p.id = m.id_persona
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
            self::SELECT_SERVICIO . ' WHERE ' . $where
            . ' ORDER BY p.apellido, p.nombre, e.nombre'
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
     * Todos los servicios que coincidan con los filtros (reportes PDF y CSV),
     * ordenados por especialidad y luego por médico.
     *
     * @param array{especialidad?: string, estado?: string, medico?: string} $filtros
     */
    public static function listarTodos(array $filtros): array
    {
        [$where, $params] = self::condiciones($filtros);

        $filas = Database::raw(
            self::SELECT_SERVICIO . ' WHERE ' . $where
            . ' ORDER BY e.nombre, p.apellido, p.nombre',
            $params
        );

        return self::normalizar((array) $filas);
    }

    /** Servicio médico por id (detalle de bloqueos). */
    public static function detalle(int $id): ?array
    {
        $filas = Database::raw(
            self::SELECT_SERVICIO . ' WHERE sm.id = :id',
            ['id' => $id]
        );

        $items = self::normalizar((array) $filas);

        return $items[0] ?? null;
    }

    /** Últimos servicios registrados (pestaña Resumen). */
    public static function recientes(int $limite = 8): array
    {
        $limite = max(1, min(20, $limite));

        $filas = Database::raw(
            self::SELECT_SERVICIO
            . ' ORDER BY sm.fecha_inicio DESC, sm.id DESC'
            . ' LIMIT ' . $limite
        );

        return self::normalizar((array) $filas);
    }

    /* ------------------------------------------------------------------
     * Alta de servicio médico (acreditación + vigencia)
     * ---------------------------------------------------------------- */

    /** Médicos con ficha activa, ordenados por nombre (selector del modal). */
    public static function medicos(): array
    {
        $filas = Database::raw(
            "SELECT m.id_persona AS id,
                    (p.nombre || ' ' || p.apellido) AS nombre_completo,
                    p.ci,
                    m.nro_colegiado
             FROM medico m
             JOIN persona p ON p.id = m.id_persona
             WHERE m.estado = TRUE AND p.estado = TRUE
             ORDER BY p.apellido, p.nombre"
        );

        $items = [];

        foreach ((array) $filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $items[] = [
                'id' => (int) ($fila['id'] ?? 0),
                'nombre_completo' => (string) ($fila['nombre_completo'] ?? ''),
                'ci' => (string) ($fila['ci'] ?? ''),
                'nro_colegiado' => (string) ($fila['nro_colegiado'] ?? ''),
            ];
        }

        return $items;
    }

    /** Acreditación (medico_especialidad) de un médico en una especialidad. */
    public static function acreditacion(int $idMedico, int $idEspecialidad): ?array
    {
        $filas = Database::raw(
            'SELECT id, id_medico, id_especialidad, institucion_formadora, nro_certificado, fecha_obtencion, estado
             FROM medico_especialidad
             WHERE id_medico = :medico AND id_especialidad = :especialidad',
            ['medico' => $idMedico, 'especialidad' => $idEspecialidad]
        );

        if (!is_array($filas) || $filas === [] || !is_array($filas[0])) {
            return null;
        }

        return $filas[0];
    }

    /** ¿Existe otro servicio ACTIVO para esa acreditación? */
    public static function tieneServicioActivo(int $idMedicoEspecialidad, ?int $excluirId = null): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM servicio_medico
                WHERE id_medico_especialidad = :acreditacion AND estado = 'ACTIVO'";
        $params = ['acreditacion' => $idMedicoEspecialidad];

        if ($excluirId !== null && $excluirId > 0) {
            $sql .= ' AND id <> :excluir';
            $params['excluir'] = $excluirId;
        }

        $filas = Database::raw($sql, $params);

        return (int) ($filas[0]['total'] ?? 0) > 0;
    }

    /** Condiciones WHERE compartidas por listar / listarTodos. */
    private static function condiciones(array $filtros): array
    {
        $especialidad = (string) ($filtros['especialidad'] ?? '');
        $estado = strtoupper(trim((string) ($filtros['estado'] ?? '')));
        $medico = trim((string) ($filtros['medico'] ?? ''));

        $donde = ['TRUE'];
        $params = [];

        if ($especialidad !== '' && (int) $especialidad > 0) {
            $donde[] = 'e.id = :especialidad';
            $params['especialidad'] = (int) $especialidad;
        }

        if (in_array($estado, ['ACTIVO', 'SUSPENDIDO', 'FINALIZADO'], true)) {
            $donde[] = 'sm.estado = :estado';
            $params['estado'] = $estado;
        }

        if ($medico !== '') {
            $donde[] = "((p.nombre || ' ' || p.apellido) ILIKE :medico"
                . ' OR p.ci ILIKE :medico OR m.nro_colegiado ILIKE :medico)';
            $params['medico'] = '%' . $medico . '%';
        }

        return [implode(' AND ', $donde), $params];
    }

    /** Normaliza las filas del SELECT de servicios. */
    private static function normalizar(array $filas): array
    {
        $items = [];

        foreach ($filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $items[] = [
                'id' => (int) ($fila['id'] ?? 0),
                'id_medico_especialidad' => (int) ($fila['id_medico_especialidad'] ?? 0),
                'id_especialidad' => (int) ($fila['id_especialidad'] ?? 0),
                'especialidad' => (string) ($fila['especialidad'] ?? ''),
                'id_medico' => (int) ($fila['id_medico'] ?? 0),
                'nro_colegiado' => (string) ($fila['nro_colegiado'] ?? ''),
                'nombre' => (string) ($fila['nombre'] ?? ''),
                'apellido' => (string) ($fila['apellido'] ?? ''),
                'nombre_completo' => (string) ($fila['nombre_completo'] ?? ''),
                'ci' => (string) ($fila['ci'] ?? ''),
                'institucion_formadora' => (string) ($fila['institucion_formadora'] ?? ''),
                'fecha_inicio' => (string) ($fila['fecha_inicio'] ?? ''),
                'fecha_fin' => (string) ($fila['fecha_fin'] ?? ''),
                'estado' => (string) ($fila['estado'] ?? 'ACTIVO'),
                'observaciones' => (string) ($fila['observaciones'] ?? ''),
                'bloqueos_activos' => (int) ($fila['bloqueos_activos'] ?? 0),
            ];
        }

        return $items;
    }

    /** SELECT base con médico, especialidad y bloqueos activos. */
    private const SELECT_SERVICIO = "
        SELECT
            sm.id,
            sm.id_medico_especialidad,
            sm.fecha_inicio,
            sm.fecha_fin,
            sm.estado,
            sm.observaciones,
            me.institucion_formadora,
            e.id AS id_especialidad,
            e.nombre AS especialidad,
            m.id_persona AS id_medico,
            m.nro_colegiado,
            p.nombre,
            p.apellido,
            (p.nombre || ' ' || p.apellido) AS nombre_completo,
            p.ci,
            (SELECT COUNT(*) FROM bloqueo_agenda b
                WHERE b.id_servicio_medico = sm.id AND b.estado = 'ACTIVO') AS bloqueos_activos
        FROM servicio_medico sm
        JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
        JOIN especialidad e ON e.id = me.id_especialidad
        JOIN medico m ON m.id_persona = me.id_medico
        JOIN persona p ON p.id = m.id_persona
    ";
}
