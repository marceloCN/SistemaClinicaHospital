<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class RecepcionCita extends Model
{
    protected string $table = 'recepcion_cita';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_cita',
        'fecha_hora_llegada',
        'id_usuario_recibe',
        'estado',
        'observaciones',
    ];

    protected bool $timestamps = false;
}
