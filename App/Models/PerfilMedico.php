<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class PerfilMedico extends Model
{
    protected string $table = 'perfil_medico';
    protected string $primaryKey = 'id_expediente';

    protected array $fillable = [
        'id_expediente',
        'grupo_sanguineo',
        'factor_rh',
        'observaciones',
    ];

    protected bool $timestamps = false;
}
