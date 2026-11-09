<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class ConsultaMedica extends Model
{
    protected string $table = 'consulta_medica';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_cita',
        'fecha_inicio',
        'fecha_fin',
        'anamnesis',
        'examen_fisico',
        'evolucion',
        'plan_tratamiento',
        'observaciones',
        'estado',
    ];

    protected bool $timestamps = false;
}
