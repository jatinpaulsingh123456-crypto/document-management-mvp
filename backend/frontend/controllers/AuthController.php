<?php

declare(strict_types=1);

namespace frontend\controllers;

use yii\web\Controller;

class AuthController extends Controller
{
    public function actionLogin(): string
    {
        return $this->render('login');
    }
}
