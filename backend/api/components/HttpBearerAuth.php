<?php

declare(strict_types=1);

namespace api\components;

use common\models\User;
use yii\filters\auth\AuthMethod;
use yii\web\IdentityInterface;

class HttpBearerAuth extends AuthMethod
{
    public function authenticate(
        $user,
        $request,
        $response
    ): ?IdentityInterface {
        $authHeader = $request->getHeaders()->get('Authorization');

        if ($authHeader === null) {
            return null;
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return null;
        }

        $token = trim($matches[1]);

        if ($token === '') {
            return null;
        }

        $identity = User::findIdentityByAccessToken(
            $token,
            static::class
        );

        if ($identity !== null) {
            $user->setIdentity($identity);

            return $identity;
        }

        $this->handleFailure($response);

        return null;
    }

    public function challenge($response): void
    {
        $response->getHeaders()->set(
            'WWW-Authenticate',
            'Bearer'
        );
    }
}