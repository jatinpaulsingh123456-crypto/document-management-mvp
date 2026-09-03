<?php

declare(strict_types=1);

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php',
);

return [
    'id' => 'app-api',

    'basePath' => dirname(__DIR__),

    'controllerNamespace' => 'api\controllers',

    'bootstrap' => [
        'log',
    ],

    'components' => [

        /*
         * Request
         */
        'request' => [
        'enableCsrfValidation' => false,
        'cookieValidationKey' => '4b2bed62ba6747b7dc165367274ad04ece39692aa6c6e65ed34afe3effe05808',

        'parsers' => [
         'application/json' => \yii\web\JsonParser::class,
            ],
        ],

        /*
         * Response
         */
        'response' => [
            'format' => \yii\web\Response::FORMAT_JSON,
            'charset' => 'UTF-8',
        ],

        /*
         * Authentication
         */
        'user' => [
            'identityClass' => \common\models\User::class,
            'enableSession' => false,
            'loginUrl' => null,
        ],

        /*
         * Database
         *
         * DB connection is loaded from common/config/main-local.php.
         * This keeps the database configuration shared between
         * console, frontend and API.
         */

        /*
         * Logging
         */
        'log' => [
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => [
                        'error',
                        'warning',
                    ],
                ],
            ],
        ],

        /*
         * URL routing
         */
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => true,

            'rules' => [

    // Health
    'GET api/v1/health' => 'health/index',
    'GET api/v1/security/activity' => 'site/security-activity',

    // Authentication
    'POST api/v1/auth/login' => 'auth/login',
    'GET api/v1/auth/me' => 'auth/me',
    'GET api/v1/dashboard' => 'dashboard/index',

    // Users
    'GET api/v1/users' => 'user/index',
    'GET api/v1/users/share-recipients/<fileId:\d+>' => 'user/share-recipients',
    'GET api/v1/users/roles' => 'user/roles',
    'GET api/v1/users/<id:\d+>' => 'user/view',
    'POST api/v1/users' => 'user/create',

    // Locations
    'GET api/v1/locations' => 'location/index',
    'POST api/v1/locations' => 'location/create',
    'GET api/v1/locations/<id:\d+>' => 'location/view',
    'PUT api/v1/locations/<id:\d+>' => 'location/update',
    'DELETE api/v1/locations/<id:\d+>' => 'location/delete',

    // Departments
    'GET api/v1/departments' => 'department/index',
    'POST api/v1/departments' => 'department/create',
    'GET api/v1/departments/<id:\d+>' => 'department/view',
    'PUT api/v1/departments/<id:\d+>' => 'department/update',
    'DELETE api/v1/departments/<id:\d+>' => 'department/delete',

    // Folders
    'GET api/v1/folders' => 'folder/index',
    'POST api/v1/folders' => 'folder/create',
    'GET api/v1/folders/<id:\d+>' => 'folder/view',
    'PUT api/v1/folders/<id:\d+>' => 'folder/update',
    'DELETE api/v1/folders/<id:\d+>' => 'folder/delete',

    // Files
    'GET api/v1/files' => 'file/index',
    'POST api/v1/files/check-duplicates' => 'file/check-duplicates',
    'GET api/v1/shared-files' => 'file/shared-files',
    'GET api/v1/my-shares' => 'file/my-shares',
    'GET api/v1/shared-history' => 'file/shared-history',
    'POST api/v1/files' => 'file/create',
    'GET api/v1/files/trash' => 'file/trash',
    'GET api/v1/files/<id:\d+>' => 'file/view',
    'PUT api/v1/files/<id:\d+>' => 'file/update',
    'DELETE api/v1/files/<id:\d+>' => 'file/delete',
    'GET api/v1/files/<id:\d+>/download' => 'file/download',
    'PUT api/v1/files/<id:\d+>/restore' => 'file/restore',
    'PUT api/v1/files/<id:\d+>/move' => 'file/move',
    'POST api/v1/files/<id:\d+>/share' => 'file/share',
    'GET api/v1/files/<id:\d+>/shares' => 'file/shares',
    'DELETE api/v1/files/<id:\d+>/shares/<shareId:\d+>' => 'file/revoke-share',
],
        ],
    ],

    /*
     * Application parameters
     */
    'params' => $params,
];
