<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Database;
use Lib\Core\Model;

class BloqueoAgenda extends Model
{
    protected string $table = 'bloqueo_agenda';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_servicio_medico',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'motivo',
        'estado',
        'id_usuario_registro',
    ];

    protected array $casts = [
        'estado' => 'boolean',
    ];

    protected bool $timestamps = false;

    /* ------------------------------------------------------------------
     * Bloqueos de agenda por servicio médico (gestor de especialidades)
     * ---------------------------------------------------------------- */

    /** Bloqueos de un servicio, del más reciente al más antiguo. */
    public static function porServicio(int $idServicio): array
    {
        $filas = Database::raw(
            "SELECT b.id,
                    b.fecha_hora_inicio,
                    b.fecha_hora_fin,
                    b.motivo,
                    b.estado,
                    b.fecha_registro,
                    COALESCE(p.nombre || ' ' || p.apellido, '—') AS registrado_por
             FROM bloqueo_agenda b
             LEFT JOIN persona p ON p.id = b.id_usuario_registro
             WHERE b.id_servicio_medico = :id
             ORDER BY b.fecha_hora_inicio DESC, b.id DESC",
            ['id' => $idServicio]
        );

        $items = [];

        foreach ((array) $filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $items[] = [
                'id' => (int) ($fila['id'] ?? 0),
                'fecha_hora_inicio' => self::legible($fila['fecha_hora_inicio'] ?? ''),
                'fecha_hora_fin' => self::legible($fila['fecha_hora_fin'] ?? ''),
                'motivo' => (string) ($fila['motivo'] ?? ''),
                'estado' => (string) ($fila['estado'] ?? 'ACTIVO'),
                'fecha_registro' => self::legible($fila['fecha_registro'] ?? ''),
                'registrado_por' => (string) ($fila['registrado_por'] ?? '—'),
                'activo' => (string) ($fila['estado'] ?? '') === 'ACTIVO',
            ];
        }

        return $items;
    }

    /** Bloqueos activos recientes de todo el sistema (pestaña Resumen). */
    public static function recientes(int $limite = 6): array
    {
        $limite = max(1, min(20, $limite));

        $filas = Database::raw(
            "SELECT b.id,
                    b.fecha_hora_inicio,
                    b.fecha_hora_fin,
                    b.motivo,
                    b.estado,
                    (p.nombre || ' ' || p.apellido) AS nombre_completo,
                    e.nombre AS especialidad,
                    m.nro_colegiado
             FROM bloqueo_agenda b
             JOIN servicio_medico sm ON sm.id = b.id_servicio_medico
             JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
             JOIN especialidad e ON e.id = me.id_especialidad
             JOIN medico m ON m.id_persona = me.id_medico
             JOIN persona p ON p.id = m.id_persona
             WHERE b.estado = 'ACTIVO'
             ORDER BY b.fecha_hora_inicio DESC, b.id DESC
             LIMIT " . $limite
        );

        $items = [];

        foreach ((array) $filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $items[] = [
                'id' => (int) ($fila['id'] ?? 0),
                'fecha_hora_inicio' => self::legible($fila['fecha_hora_inicio'] ?? ''),
                'fecha_hora_fin' => self::legible($fila['fecha_hora_fin'] ?? ''),
                'motivo' => (string) ($fila['motivo'] ?? ''),
                'nombre_completo' => (string) ($fila['nombre_completo'] ?? ''),
                'especialidad' => (string) ($fila['especialidad'] ?? ''),
                'nro_colegiado' => (string) ($fila['nro_colegiado'] ?? ''),
            ];
        }

        return $items;
    }

    /**
     * '2026-11-19 15:02:00-04' o ISO → '19/11/2026 15:02' (solo pantalla).
     */
    private static function legible(string $valor): string
    {
        $valor = trim(str_replace('T', ' ', $valor));

        if ($valor === '') {
            return '';
        }

        $fecha = \DateTime::createFromFormat('Y-m-d H:i:sP', $valor)
            ?: \DateTime::createFromFormat('Y-m-d H:i:s', $valor)
            ?: \DateTime::createFromFormat('Y-m-d H:i', $valor);

        if ($fecha === false) {
            return substr($valor, 0, 16);
        }

        return $fecha->format('d/m/Y H:i');
    }

    /** Cantidad de bloqueos activos de un servicio. */
    public static function contarActivos(int $idServicio): int
    {
        $filas = Database::raw(
            "SELECT COUNT(*) AS total
             FROM bloqueo_agenda
             WHERE id_servicio_medico = :id AND estado = 'ACTIVO'",
            ['id' => $idServicio]
        );

        return (int) ($filas[0]['total'] ?? 0);
    }

    /** Detecta solapamiento con otros bloqueos activos del mismo servicio. */
    public static function solapado(int $idServicio, string $inicio, string $fin, ?int $excluirId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS total
                FROM bloqueo_agenda
                WHERE id_servicio_medico = :id
                  AND estado = \'ACTIVO\'
                  AND fecha_hora_inicio < :fin
                  AND fecha_hora_fin > :inicio';
        $params = [
            'id' => $idServicio,
            'inicio' => $inicio,
            'fin' => $fin,
        ];

        if ($excluirId !== null && $excluirId > 0) {
            $sql .= ' AND id <> :excluir';
            $params['excluir'] = $excluirId;
        }

        $filas = Database::raw($sql, $params);

        return (int) ($filas[0]['total'] ?? 0) > 0;
    }

    /**
     * Bloqueos activos con rango aún no concluido, con su médico y la
     * cantidad de citas reprogramables que afectan (recepción de citas).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function activos(): array
    {
        $filas = Database::raw(
            "SELECT b.id,
                    b.id_servicio_medico,
                    b.fecha_hora_inicio,
                    b.fecha_hora_fin,
                    b.motivo,
                    (p.nombre || ' ' || p.apellido) AS nombre_completo,
                    e.nombre AS especialidad,
                    m.nro_colegiado,
                    (SELECT COUNT(*)
                     FROM cita c
                     JOIN ficha_agenda fa ON fa.id = c.id_ficha_agenda
                     JOIN horario_atencion h ON h.id = fa.id_horario
                     WHERE h.id_servicio_medico = b.id_servicio_medico
                       AND fa.estado = 'CERRADA'
                       AND fa.fecha_hora_inicio >= b.fecha_hora_inicio
                       AND fa.fecha_hora_inicio < b.fecha_hora_fin
                       AND c.estado IN ('PROGRAMADA', 'REPROGRAMADA')
                    ) AS citas_afectadas
             FROM bloqueo_agenda b
             JOIN servicio_medico sm ON sm.id = b.id_servicio_medico
             JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
             JOIN medico m ON m.id_persona = me.id_medico
             JOIN persona p ON p.id = m.id_persona
             JOIN especialidad e ON e.id = me.id_especialidad
             WHERE b.estado = 'ACTIVO'
               AND b.fecha_hora_fin >= NOW()
             ORDER BY b.fecha_hora_inicio"
        );

        $items = [];

        foreach ((array) $filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }

            $items[] = [
                'id' => (int) ($fila['id'] ?? 0),
                'id_servicio_medico' => (int) ($fila['id_servicio_medico'] ?? 0),
                'fecha_hora_inicio' => self::legible($fila['fecha_hora_inicio'] ?? ''),
                'fecha_hora_fin' => self::legible($fila['fecha_hora_fin'] ?? ''),
                'inicio_crudo' => (string) ($fila['fecha_hora_inicio'] ?? ''),
                'fin_crudo' => (string) ($fila['fecha_hora_fin'] ?? ''),
                'motivo' => (string) ($fila['motivo'] ?? ''),
                'nombre_completo' => (string) ($fila['nombre_completo'] ?? ''),
                'especialidad' => (string) ($fila['especialidad'] ?? ''),
                'nro_colegiado' => (string) ($fila['nro_colegiado'] ?? ''),
                'citas_afectadas' => (int) ($fila['citas_afectadas'] ?? 0),
            ];
        }

        return $items;
    }

    /** Detalle de un bloqueo activo (para la reprogramación). */
    public static function activo(int $id): ?array
    {
        $items = array_values(array_filter(
            self::activos(),
            static fn (array $bloqueo): bool => $bloqueo['id'] === $id
        ));

        return $items[0] ?? null;
    }
}
