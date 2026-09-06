<?php

declare(strict_types=1);

$environmentConfig = require __DIR__ . '/environment.php';
$commonEnvironmentConfig = require __DIR__ . '/environments/common.php';

return [
    'bootstrap' => [
        \common\bootstrap\MailerBootstrap::class,
    ],

    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],

    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',

    'components' => [
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
    ],

    'params' => array_merge(
        $commonEnvironmentConfig['params'] ?? [],
        $environmentConfig['params'] ?? [],
    ),
];
