<?php

declare(strict_types=1);

$allowedEnvironments = [
    'development',
    'qa',
    'staging',
    'production',
];

$environment = getenv('APP_ENV') ?: 'development';

if (!in_array($environment, $allowedEnvironments, true)) {
    throw new RuntimeException(
        sprintf(
            'Invalid APP_ENV "%s". Allowed values: %s',
            $environment,
            implode(', ', $allowedEnvironments)
        )
    );
}

$environmentConfig = require __DIR__ . '/environments/' . $environment . '.php';

return array_merge(
    [
        'name' => $environment,
        'yiiEnvironment' => $environment === 'development' ? 'dev' : 'prod',
        'yiiDebug' => $environment === 'development',
        'isProduction' => $environment === 'production',
        'isDevelopment' => $environment === 'development',
        'isQa' => $environment === 'qa',
        'isStaging' => $environment === 'staging',
    ],
    $environmentConfig
);
