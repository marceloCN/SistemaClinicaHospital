<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class RolPermiso extends Model
{
    protected string $table = 'rol_permiso';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_rol',
        'id_permiso',
        'estado',
    ];

    protected array $casts = [
        'estado' => 'boolean',
    ];

    protected bool $timestamps = false;
}
