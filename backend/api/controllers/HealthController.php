<?php

declare(strict_types=1);

namespace api\controllers;

use yii\rest\Controller;

class HealthController extends Controller
{
    public function actionIndex(): array
    {
        return [
            'status' => 'ok',
            'message' => 'Document Management API is running.',
        ];
    }
}
