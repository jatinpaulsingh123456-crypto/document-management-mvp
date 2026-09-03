<?php

declare(strict_types=1);

return [
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'mysql:host=' . (getenv('DB_HOST') ?: 'mysql') . ';dbname=' . (getenv('DB_NAME') ?: 'document_management'),
            'username' => getenv('DB_USER') ?: 'document_management',
            'password' => getenv('DB_PASSWORD') ?: 'document_management',
            'charset' => 'utf8mb4',
        ],
    ],
];
