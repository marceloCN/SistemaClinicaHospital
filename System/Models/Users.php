<?php

declare(strict_types=1);

namespace System\Models;

use Lib\Core\Model;

class Users extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'name', 'email', 'password', 'status', 'created_at', 'updated_at',
    ];

    protected array $casts = [
        'status' => 'boolean',
    ];
}
