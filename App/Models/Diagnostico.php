<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class Diagnostico extends Model
{
    protected string $table = 'diagnostico';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_consulta',
        'descripcion',
        'tipo',
        'observaciones',
    ];

    protected bool $timestamps = false;
}
