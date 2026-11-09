<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class AntecedenteMedico extends Model
{
    protected string $table = 'antecedente_medico';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_expediente',
        'tipo',
        'descripcion',
        'estado',
    ];

    protected array $casts = [
        'estado' => 'boolean',
    ];

    protected bool $timestamps = false;
}
