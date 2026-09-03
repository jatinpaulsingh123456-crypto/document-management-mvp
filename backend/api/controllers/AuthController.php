<?php

declare(strict_types=1);

namespace api\controllers;

use api\components\HttpBearerAuth;
use common\models\User;
use Yii;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\UnauthorizedHttpException;

class AuthController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => [
                'login',
            ],
        ];

        return $behaviors;
    }

    public function actionLogin(): array
    {
        $body = Yii::$app->request->bodyParams;

        $login = trim((string) ($body['login'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($login === '' || $password === '') {
            throw new BadRequestHttpException(
                'Login and password are required.'
            );
        }

        $user = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? User::findByEmail($login)
            : User::findByUsername($login);

        if ($user === null || !$user->validatePassword($password)) {
            throw new UnauthorizedHttpException(
                'Invalid username/email or password.'
            );
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            throw new UnauthorizedHttpException(
                'User account is not active.'
            );
        }

        $user->generateAuthKey();
        $user->last_login_at = date('Y-m-d H:i:s');
        $user->save(false);

        return [
            'success' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'name' => $user->name,
                'role_id' => $user->role_id,
                'location_id' => $user->location_id,
                'department_id' => $user->department_id,
            ],
            'auth_key' => $user->auth_key,
        ];
    }

    public function actionMe(): array
    {
        /** @var User|null $user */
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        return [
            'success' => true,
            'data' => [
                'id' => (int) $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'name' => $user->name,
                'status' => (int) $user->status,

                'role' => $user->role === null
                    ? null
                    : [
                        'id' => (int) $user->role->id,
                        'code' => $user->role->code,
                        'name' => $user->role->name,
                    ],

                'location' => $user->location === null
                    ? null
                    : [
                        'id' => (int) $user->location->id,
                        'name' => $user->location->name,
                        'code' => $user->location->code,
                        'country' => $user->location->country,
                    ],

                'department' => $user->department === null
                    ? null
                    : [
                        'id' => (int) $user->department->id,
                        'name' => $user->department->name,
                        'code' => $user->department->code,
                    ],

                'manager' => $user->manager === null
                    ? null
                    : [
                        'id' => (int) $user->manager->id,
                        'username' => $user->manager->username,
                        'name' => $user->manager->name,
                    ],
            ],
        ];
    }}