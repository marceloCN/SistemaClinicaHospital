<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class TipoEstudio extends Model
{
    protected string $table = 'tipo_estudio';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'nombre',
        'descripcion',
        'estado',
    ];

    protected array $casts = [
        'estado' => 'boolean',
    ];

    protected bool $timestamps = false;
}
