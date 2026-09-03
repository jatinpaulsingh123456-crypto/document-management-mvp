<?php

declare(strict_types=1);

return [
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'mysql:host=127.0.0.1;dbname=document_management',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8mb4',
        ],
    ],
];
