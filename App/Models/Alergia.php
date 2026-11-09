<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class Alergia extends Model
{
    protected string $table = 'alergia';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_expediente',
        'sustancia',
        'tipo',
        'reaccion',
        'gravedad',
        'observaciones',
        'estado',
    ];

    protected array $casts = [
        'estado' => 'boolean',
    ];

    protected bool $timestamps = false;
}
