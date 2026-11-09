<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class ReprogramacionCita extends Model
{
    protected string $table = 'reprogramacion_cita';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_cita',
        'id_ficha_anterior',
        'id_ficha_nueva',
        'id_usuario_reprograma',
        'motivo',
    ];

    protected bool $timestamps = false;
}
