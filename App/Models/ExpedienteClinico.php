<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class ExpedienteClinico extends Model
{
    protected string $table = 'expediente_clinico';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_paciente',
        'observaciones_generales',
        'estado',
    ];

    protected array $casts = [
        'estado' => 'boolean',
    ];

    protected bool $timestamps = false;
}
