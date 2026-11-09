<?php

declare(strict_types=1);

namespace System\Models;

use Lib\Core\Model;

class Settings extends Model
{
    protected string $table = 'settings';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'site_name', 'timezone', 'maintenance_mode', 'created_at', 'updated_at',
    ];

    protected array $casts = [
        'maintenance_mode' => 'boolean',
    ];
}
