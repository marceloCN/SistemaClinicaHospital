<?php

declare(strict_types=1);

use Lib\Support\Env;

return [
    'default' => Env::get('DB_CONNECTION', 'mysql'),
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => Env::get('DB_HOST', 'localhost'),
            'port' => Env::getInt('DB_PORT', 3306),
            'database' => Env::get('DB_DATABASE', 'gupadis'),
            'username' => Env::get('DB_USERNAME', 'root'),
            'password' => Env::get('DB_PASSWORD', ''),
            'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
            'options' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => Env::get('DB_HOST', 'localhost'),
            'port' => Env::getInt('DB_PORT', 5432),
            'database' => Env::get('DB_DATABASE', 'gupadis'),
            'username' => Env::get('DB_USERNAME', 'postgres'),
            'password' => Env::get('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'options' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
    ],
];
