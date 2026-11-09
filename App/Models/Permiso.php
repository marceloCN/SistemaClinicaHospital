<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class Permiso extends Model
{
    protected string $table = 'permiso';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'clave',
        'nombre',
        'descripcion',
        'modulo',
    ];

    protected bool $timestamps = false;
       
}
