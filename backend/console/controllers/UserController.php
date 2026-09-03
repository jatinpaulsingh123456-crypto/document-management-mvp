<?php

declare(strict_types=1);

namespace console\controllers;

use common\models\User;
use yii\console\Controller;
use yii\console\ExitCode;

class UserController extends Controller
{
    /**
     * Create a new user.
     *
     * Usage:
     *
     * php yii user/create
     * php yii user/create employee employee@example.com 'Employee@12345'
     *
     * Defaults are intended for creating the initial admin user.
     */
    public function actionCreate(
        string $username = 'admin',
        string $email = 'admin@example.com',
        string $password = 'Admin@12345'
    ): int {
        if (User::find()->where(['username' => $username])->exists()) {
            $this->stderr(
                "User already exists: {$username}\n"
            );

            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (User::find()->where(['email' => $email])->exists()) {
            $this->stderr(
                "Email already exists: {$email}\n"
            );

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $user = new User();

        $user->username = $username;
        $user->email = $email;
        $user->name = 'System Administrator';

        $user->password_hash = \Yii::$app->security
            ->generatePasswordHash($password);

        $user->generateAuthKey();

        $user->status = User::STATUS_ACTIVE;
        $user->role_id = 1;

        if (!$user->save()) {
            $this->stderr(
                "Failed to create user:\n"
            );

            foreach ($user->getErrors() as $attribute => $errors) {
                $this->stderr(
                    "{$attribute}: "
                    . implode(', ', $errors)
                    . "\n"
                );
            }

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout(
            "User created successfully.\n"
        );

        $this->stdout(
            "Username: {$username}\n"
        );

        $this->stdout(
            "Email: {$email}\n"
        );

        return ExitCode::OK;
    }

    /**
     * Set/reset a user's password.
     *
     * Usage:
     *
     * php yii user/set-password employee 'Employee@12345'
     */
    public function actionSetPassword(
        string $username,
        string $password
    ): int {
        $user = User::find()
            ->where(['username' => $username])
            ->one();

        if ($user === null) {
            $this->stderr(
                "User not found: {$username}\n"
            );

            return ExitCode::DATAERR;
        }

        if ($password === '') {
            $this->stderr(
                "Password cannot be empty.\n"
            );

            return ExitCode::USAGE;
        }

        $user->password_hash = \Yii::$app->security
            ->generatePasswordHash($password);

        if (!$user->save(false, ['password_hash'])) {
            $this->stderr(
                "Failed to update password.\n"
            );

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout(
            "Password updated successfully for: {$username}\n"
        );

        return ExitCode::OK;
    }
}