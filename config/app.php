<?php

declare(strict_types=1);

use Lib\Support\Env;

return [
    'name' => Env::get('APP_NAME', 'Sistema CMS'),
    'description' => Env::get('APP_DESCRIPTION', ''),
    'env' => Env::get('APP_ENV', 'production'),
    'url' => rtrim(Env::get('APP_URL', 'http://localhost'), '/'),
    'debug' => Env::getBool('APP_DEBUG', false),
    'key' => Env::get('APP_KEY', ''),
    'timezone' => Env::get('TIMEZONE', 'America/La_Paz'),
    'language' => Env::get('LANGUAGE', 'es'),
    'session_lifetime' => Env::getInt('SESSION_LIFETIME', 120),
];
