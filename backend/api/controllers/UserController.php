<?php

declare(strict_types=1);

namespace api\controllers;

use common\models\User;
use common\models\Role;
use common\models\File;
use common\services\AuthorizationService;
use Yii;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

class UserController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
            'class' => \api\components\HttpBearerAuth::class,
        ];

        return $behaviors;
    }

    /**
     * GET /api/v1/users
     */
    public function actionIndex(): array
    {
        $users = User::find()
            ->with([
                'role',
                'location',
                'department',
                'manager',
            ])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        return [
            'success' => true,
            'data' => array_map(
                fn(User $user): array => $this->serializeUser($user),
                $users
            ),
        ];
    }

    /**
     * GET /api/v1/users/share-recipients/{fileId}
     *
     * Return active users who may be selected as recipients
     * for the selected file.
     */
    public function actionShareRecipients(int $fileId): array
    {
        $currentUser = Yii::$app->user->identity;

        if ($currentUser === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        $file = File::findOne($fileId);

        if ($file === null || $file->status !== File::STATUS_ACTIVE) {
            throw new NotFoundHttpException('File not found.');
        }

        /** @var AuthorizationService $authorizationService */
        $authorizationService = Yii::$container->get(
            AuthorizationService::class
        );

        if (!$authorizationService->canManageFile(
            $currentUser,
            $file
        )) {
            throw new ForbiddenHttpException(
                'You are not authorized to share this file.'
            );
        }

        $users = User::find()
            ->with([
                'role',
                'location',
                'department',
                'manager',
            ])
            ->where([
                'status' => User::STATUS_ACTIVE,
            ])
            ->andWhere([
                'location_id' => $file->location_id,
                'department_id' => $file->department_id,
            ])
            ->andWhere([
                '<>',
                'id',
                (int) $currentUser->id,
            ])
            ->orderBy([
                'name' => SORT_ASC,
                'id' => SORT_ASC,
            ])
            ->all();

        return [
            'success' => true,
            'data' => array_map(
                fn (User $user): array => $this->serializeUser($user),
                $users
            ),
        ];
    }

    /**
     * GET /api/v1/users/roles
     */
    public function actionRoles(): array
    {
        return [
            'success' => true,
            'data' => array_map(
                static fn (Role $role): array => [
                    'id' => (int) $role->id,
                    'code' => $role->code,
                    'name' => $role->name,
                ],
                Role::find()
                    ->orderBy(['id' => SORT_ASC])
                    ->all()
            ),
        ];
    }

    /**
     * GET /api/v1/users/{id}
     */
    public function actionView(int $id): array
    {
        $user = User::find()
            ->with([
                'role',
                'location',
                'department',
                'manager',
            ])
            ->where(['id' => $id])
            ->one();

        if ($user === null) {
            throw new NotFoundHttpException('User not found.');
        }

        return [
            'success' => true,
            'data' => $this->serializeUser($user),
        ];
    }

    /**
     * POST /api/v1/users
     */
    public function actionCreate(): array
    {
        $body = Yii::$app->request->bodyParams;

        $user = new User();

        $user->username = trim((string) ($body['username'] ?? ''));
        $user->email = trim((string) ($body['email'] ?? ''));
        $user->name = trim((string) ($body['name'] ?? ''));

        $user->role_id = isset($body['role_id'])
            ? (int) $body['role_id']
            : null;

        $user->location_id = isset($body['location_id'])
            ? (int) $body['location_id']
            : null;

        $user->department_id = isset($body['department_id'])
            ? (int) $body['department_id']
            : null;

        $user->manager_id = isset($body['manager_id'])
            ? (int) $body['manager_id']
            : null;

        $user->status = isset($body['status'])
            ? (int) $body['status']
            : User::STATUS_ACTIVE;

        $password = (string) ($body['password'] ?? '');

        if ($password === '') {
            throw new BadRequestHttpException(
                'Password is required.'
            );
        }

        $user->setPassword($password);
        $user->generateAuthKey();

        if (!$user->validate()) {
            throw new BadRequestHttpException(
                implode(' ', $user->getErrorSummary(true))
            );
        }

        if (!$user->save(false)) {
            throw new BadRequestHttpException(
                'Unable to create user.'
            );
        }

        $user->refresh();
        $user->populateRelation(
            'role',
            $user->role
        );

        return [
            'success' => true,
            'message' => 'User created successfully.',
            'data' => $this->serializeUser($user),
        ];
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'name' => $user->name,
            'status' => $user->status,

            'role' => $user->role === null
                ? null
                : [
                    'id' => $user->role->id,
                    'code' => $user->role->code,
                    'name' => $user->role->name,
                ],

            'location' => $user->location === null
                ? null
                : [
                    'id' => $user->location->id,
                    'name' => $user->location->name,
                    'code' => $user->location->code,
                    'country' => $user->location->country,
                ],

            'department' => $user->department === null
                ? null
                : [
                    'id' => $user->department->id,
                    'name' => $user->department->name,
                    'code' => $user->department->code,
                ],

            'manager' => $user->manager === null
                ? null
                : [
                    'id' => $user->manager->id,
                    'username' => $user->manager->username,
                    'name' => $user->manager->name,
                ],
        ];
    }
}