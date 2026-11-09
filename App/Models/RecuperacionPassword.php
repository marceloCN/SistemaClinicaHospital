<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class RecuperacionPassword extends Model
{
    protected string $table = 'recuperacion_password';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_cuenta_persona',
        'token',
        'estado',
        'fecha_expiracion',
        'fecha_uso',
    ];

    protected bool $timestamps = false;
}
