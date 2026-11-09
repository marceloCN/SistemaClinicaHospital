<?php

declare(strict_types=1);

use Lib\Support\Env;

return [
    'default' => Env::get('MAIL_MAILER', 'smtp'),

    'from' => [
        'address' => Env::get('MAIL_FROM_ADDRESS', ''),
        'name' => Env::get('MAIL_FROM_NAME', ''),
    ],

    'mailers' => [
        'smtp' => [
            'host' => Env::get('MAIL_HOST', ''),
            'port' => Env::getInt('MAIL_PORT', 587),
            'username' => Env::get('MAIL_USERNAME', ''),
            'password' => Env::get('MAIL_PASSWORD', ''),
            'encryption' => Env::get('MAIL_ENCRYPTION', ''),
            'verify_peer' => Env::getBool('MAIL_VERIFY_PEER', true),
            'timeout' => Env::getInt('MAIL_TIMEOUT', 15),
        ],
    ],
];
