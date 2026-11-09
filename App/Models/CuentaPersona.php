<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class CuentaPersona extends Model
{
    protected string $table = 'cuenta_persona';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_persona',
        'usuario',
        'correo',
        'password_hash',
        'intentos_fallidos',
        'bloqueado',
        'bloqueado_hasta',
        'ultimo_acceso',
        'remember_token',
    ];

    protected array $casts = [
        'intentos_fallidos' => 'integer',
        'bloqueado' => 'boolean',
    ];

    protected bool $timestamps = false;
}
