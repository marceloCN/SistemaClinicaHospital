<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class Rol extends Model
{
    protected string $table = 'rol';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'clave',
        'nombre',
    ];

    protected bool $timestamps = false;
}
