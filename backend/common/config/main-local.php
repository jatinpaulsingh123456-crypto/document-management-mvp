<?php

return [
    'container' => [
        'singletons' => [
            \common\services\AuthorizationService::class => [
                'class' => \common\services\AuthorizationService::class,
            ],

            \common\services\DataScopeService::class => [
                'class' => \common\services\DataScopeService::class,
            ],

            \common\storage\StorageInterface::class => [
                'class' => \common\storage\LocalStorage::class,
                'basePath' => dirname(__DIR__) . '/storage/files',
            ],

            \common\services\FileUploadService::class => [
                'class' => \common\services\FileUploadService::class,
            ],

            \yii\mail\MailerInterface::class => [
                'class' => \yii\symfonymailer\Mailer::class,
                'viewPath' => '@common/mail',
                'useFileTransport' => true,
            ],
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

        'mailer' => \yii\mail\MailerInterface::class,
    ],
];
