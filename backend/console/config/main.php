<?php

declare(strict_types=1);

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php',
);

return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'console\controllers',

    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@npm-asset',
    ],

    'controllerMap' => [
        'fixture' => [
            'class' => \yii\console\controllers\FixtureController::class,
            'namespace' => 'common\fixtures',
        ],
    ],

    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'mysql:host=' . (getenv('DB_HOST') ?: 'mysql') . ';dbname=' . (getenv('DB_NAME') ?: 'document_management'),
            'username' => getenv('DB_USER') ?: 'document_management',
            'password' => getenv('DB_PASSWORD') ?: 'document_management',
            'charset' => 'utf8mb4',
        ],

        'log' => [
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
    ],

    'params' => $params,
];
