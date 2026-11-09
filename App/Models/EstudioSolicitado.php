<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class EstudioSolicitado extends Model
{
    protected string $table = 'estudio_solicitado';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_consulta',
        'id_diagnostico',
        'id_tipo_estudio',
        'indicacion',
        'estado',
        'fecha_realizacion',
        'resultado',
        'fecha_resultado',
        'observaciones',
    ];

    protected bool $timestamps = false;
}
