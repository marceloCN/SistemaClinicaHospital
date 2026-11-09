<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Database;
use Lib\Core\Model;

class FichaAgenda extends Model
{
    protected string $table = 'ficha_agenda';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_horario',
        'fecha',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'capacidad',
        'estado',
    ];

    protected bool $timestamps = false;

    
    //fichas disponibles por id de servicio_medico y la fecha en ficha_agenda
    public static function fichasDisponibles (int $idServicioMedico, string $fecha): array 
    {
        $filas = Database::raw(
            "SELECT fa.id, fa.fecha, fa.fecha_hora_inicio, fa.fecha_hora_fin
             FROM ficha_agenda fa, horario_atencion ha, servicio_medico sm
            where fa.estado = 'ABIERTA' and fa.fecha = :fecha and fa.id_horario = ha.id and ha.id_servicio_medico = sm.id and sm.id = :id",
            ['fecha' => $fecha, 'id' => $idServicioMedico]
        );
        return $filas ? : [];
    }

    /**
     * Ficha con los datos necesarios para reservar (horario, servicio,
     * médico y especialidad), bloqueada con FOR UPDATE.
     *
     * El candado serializa a los pacientes que intentan reservar la misma
     * ficha: el segundo transacción espera a que la primera confirme y
     * luego ve el estado ya actualizado.
     *
     * @return array<string, mixed>|null
     */
    public static function bloquearParaReserva(int $id): ?array
    {
        $filas = Database::raw(
            'SELECT fa.id,
                    fa.estado,
                    fa.fecha,
                    fa.fecha_hora_inicio,
                    fa.fecha_hora_fin,
                    h.estado AS horario_estado,
                    h.fecha_hasta,
                    (h.fecha_hasta IS NULL OR h.fecha_hasta >= CURRENT_DATE) AS vigente,
                    (fa.fecha_hora_inicio > NOW()) AS futuro,
                    sm.estado AS servicio_estado,
                    me.id_medico,
                    me.id_especialidad
             FROM ficha_agenda fa
             JOIN horario_atencion h ON h.id = fa.id_horario
             JOIN servicio_medico sm ON sm.id = h.id_servicio_medico
             JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
             WHERE fa.id = :id
             FOR UPDATE',
            ['id' => $id]
        );

        if (!is_array($filas) || $filas === [] || !is_array($filas[0])) {
            return null;
        }

        $fila = $filas[0];

        // PDO devuelve los booleanos de PostgreSQL como bool, 't'/'f' o '1'/'0'.
        $fila['horario_estado'] = filter_var($fila['horario_estado'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $fila['vigente'] = filter_var($fila['vigente'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $fila['futuro'] = filter_var($fila['futuro'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $fila;
    }
}

