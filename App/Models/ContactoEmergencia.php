<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class ContactoEmergencia extends Model
{
    protected string $table = 'contacto_emergencia';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_paciente',
        'nombre',
        'parentesco',
        'telefono',
        'correo',
        'estado',
    ];

    protected array $casts = [
        'estado' => 'boolean',
    ];

    protected bool $timestamps = false;
}
