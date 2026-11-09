<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Database;
use Lib\Core\Model;

class Medico extends Model
{
    protected string $table = 'medico';
    protected string $primaryKey = 'id_persona';

    protected array $fillable = [
        'id_persona',
        'nro_colegiado',
        'fecha_ingreso',
        'biografia',
        'estado',
    ];

    protected array $casts = [
        'estado' => 'boolean',
    ];

    protected bool $timestamps = false;

    /**
     * Directorio de médicos activos.
     *
     * Se consideran médicos con una acreditación activa en servicio_medico.
     * Si esa tabla aún no tiene registros, se usa medico_especialidad.
     */
    public static function directorio(?string $especialidad = null): array
    {
        $filtro = '';
        $parametros = [];
        $especialidad = trim((string) $especialidad);

        if ($especialidad !== '') {
            $filtro = ' AND e.nombre ILIKE :especialidad';
            $parametros['especialidad'] = '%' . $especialidad . '%';
        }

        $servicios = ServicioMedico::hayRegistros();

        $unionServicio = $servicios
            ? 'JOIN servicio_medico sm ON sm.id_medico_especialidad = me.id AND sm.estado = \'ACTIVO\''
            : '';

        $sql = "
            SELECT
                p.id,
                p.nombre,
                p.apellido,
                p.telefono,
                p.correo,
                m.nro_colegiado,
                m.biografia,
                STRING_AGG(DISTINCT e.nombre, ', ' ORDER BY e.nombre) AS especialidades
            FROM persona p
            JOIN medico m
                ON m.id_persona = p.id
                AND m.estado = TRUE
            JOIN medico_especialidad me
                ON me.id_medico = m.id_persona
                AND me.estado = TRUE
            JOIN especialidad e
                ON e.id = me.id_especialidad
                AND e.estado = TRUE
            {$unionServicio}
            WHERE p.estado = TRUE
            {$filtro}
            GROUP BY p.id, p.nombre, p.apellido, p.telefono, p.correo, m.nro_colegiado, m.biografia
            ORDER BY p.apellido, p.nombre
        ";

        return Database::raw($sql, $parametros);
    }
}
