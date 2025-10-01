<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'default' => [
        'transport' => [
            'scheme' => 'smtp',
            'host' => env('MAIL_HOST', 'mailhog'),
            'port' => (int) env('MAIL_PORT', 1025),
            'username' => env('MAIL_USERNAME', ''),
            'password' => env('MAIL_PASSWORD', ''),
            'encryption' => env('MAIL_ENCRYPTION', null),
        ],
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'noreply@hyperf-bank.com'),
            'name' => env('MAIL_FROM_NAME', 'Hyperf Bank'),
        ],
    ],
];
