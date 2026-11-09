<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class MedicamentoPaciente extends Model
{
    protected string $table = 'medicamento_paciente';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_expediente',
        'nombre_medicamento',
        'dosis',
        'frecuencia',
        'fecha_inicio',
        'fecha_fin',
        'prescrito_por',
        'observaciones',
        'estado',
    ];

    protected array $casts = [
        'estado' => 'boolean',
    ];

    protected bool $timestamps = false;
}
