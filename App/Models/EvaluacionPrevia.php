<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class EvaluacionPrevia extends Model
{
    protected string $table = 'evaluacion_previa';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_cita',
        'id_personal_registro',
        'motivo_referido',
        'anamnesis_preliminar',
        'presion_sistolica',
        'presion_diastolica',
        'frecuencia_cardiaca',
        'frecuencia_respiratoria',
        'temperatura',
        'saturacion_oxigeno',
        'peso_kg',
        'talla_cm',
        'observaciones',
        'estado',
    ];

    protected bool $timestamps = false;
}
