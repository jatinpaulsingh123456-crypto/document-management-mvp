<?php

declare(strict_types=1);

namespace api\controllers;

use common\models\Folder;
use common\services\AuthorizationService;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UnauthorizedHttpException;

class FolderController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        return [
            'authenticator' => [
                'class' => HttpBearerAuth::class,
            ],
        ];
    }

    /**
     * GET /api/v1/folders
     *
     * Return only folders the current user may view.
     */
    /**
 * GET /api/v1/folders
 *
 * Return only folders the current user may view.
 */
    public function actionIndex(): array
    {
    $user = Yii::$app->user->identity;

    if ($user === null) {
        throw new UnauthorizedHttpException(
            'Authentication required.'
        );
    }

    $authorizationService = Yii::$container->get(
        AuthorizationService::class
    );

    $parentId = Yii::$app->request->get('parent_id');

    $query = Folder::find();

    if ($parentId !== null && $parentId !== '') {
        if (!ctype_digit((string) $parentId)) {
            throw new BadRequestHttpException(
                'parent_id must be a valid integer.'
            );
        }

        $query->andWhere([
            'parent_id' => (int) $parentId,
        ]);
    } else {
        $query->andWhere([
            'parent_id' => null,
        ]);
    }

    $folders = $query
        ->orderBy(['id' => SORT_DESC])
        ->all();

    $visibleFolders = array_filter(
        $folders,
        fn (Folder $folder): bool =>
            $authorizationService->canViewFolder(
                $user,
                $folder
            )
    );

    return [
        'success' => true,
        'data' => array_values(
            array_map(
                fn (Folder $folder): array =>
                    $this->serializeFolder($folder),
                $visibleFolders
            )
        ),
    ];
}
    /**
     * GET /api/v1/folders/{id}
     */
    public function actionView(int $id): array
    {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        $folder = Folder::findOne($id);

        if ($folder === null) {
            throw new NotFoundHttpException(
                'Folder not found.'
            );
        }

        $authorizationService = Yii::$container->get(
            AuthorizationService::class
        );

        if (!$authorizationService->canViewFolder(
            $user,
            $folder
        )) {
            throw new ForbiddenHttpException(
                'You are not authorized to view this folder.'
            );
        }

        return [
            'success' => true,
            'data' => $this->serializeFolder($folder),
        ];
    }

    /**
     * POST /api/v1/folders
     */
    public function actionCreate(): array
    {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        $data = Yii::$app->request->bodyParams;

        $name = trim(
            (string) ($data['name'] ?? '')
        );

        if ($name === '') {
            throw new BadRequestHttpException(
                'Folder name is required.'
            );
        }

        $authorizationService = Yii::$container->get(
            AuthorizationService::class
        );

        /*
         * If a parent folder is supplied, the user must be able
         * to manage that parent before creating a child inside it.
         */
        $parentId = $this->nullableInt(
            $data['parent_id'] ?? null
        );

        $parent = null;

        if ($parentId !== null) {
            $parent = Folder::findOne($parentId);

            if ($parent === null) {
                throw new BadRequestHttpException(
                    'Parent folder not found.'
                );
            }

            if (!$authorizationService->canManageFolder(
                $user,
                $parent
            )) {
                throw new ForbiddenHttpException(
                    'You are not authorized to create a folder here.'
                );
            }
        }

        $folder = new Folder();

        $folder->name = $name;

        $folder->folder_type = (string) (
            $data['folder_type']
            ?? Folder::TYPE_CUSTOM
        );

        $folder->parent_id = $parentId;

        /*
         * Do not allow an employee to freely assign ownership
         * to another user.
         */
        $ownerUserId = $this->nullableInt(
    $data['owner_user_id'] ?? null
);

if ($ownerUserId !== null) {
    /*
     * Global users may assign ownership to any user.
     */
    if ($user->hasGlobalAccess()) {
        $folder->owner_user_id = $ownerUserId;
    }
    /*
     * Employees may only create a folder owned by themselves.
     */
    elseif (
        $user->isEmployee()
        && $ownerUserId === (int) $user->id
    ) {
        $folder->owner_user_id = (int) $user->id;
    }
    else {
        throw new ForbiddenHttpException(
            'You are not authorized to assign folder ownership.'
        );
    }
} else {
    /*
     * If an employee creates a user folder without explicitly
     * providing owner_user_id, make it their own folder.
     */
    if (
        $user->isEmployee()
        && $folder->folder_type === Folder::TYPE_USER
    ) {
        $folder->owner_user_id = (int) $user->id;
    } else {
        $folder->owner_user_id = null;
    }
}

        /*
         * Location/department are normally inherited from the
         * parent folder when a parent exists.
         */
        if ($parent !== null) {
            $folder->location_id = $parent->location_id;
            $folder->department_id = $parent->department_id;
        } else {
            $folder->location_id = $this->nullableInt(
                $data['location_id'] ?? null
            );

            $folder->department_id = $this->nullableInt(
                $data['department_id'] ?? null
            );

            /*
             * Non-global users cannot create a root folder in an
             * arbitrary location/department.
             */
            if (!$user->hasGlobalAccess()) {
                if (
                    $folder->location_id !== null
                    && $user->location_id !== null
                    && (int) $folder->location_id !==
                        (int) $user->location_id
                ) {
                    throw new ForbiddenHttpException(
                        'You are not authorized to create a folder in this location.'
                    );
                }

                if (
                    $folder->department_id !== null
                    && $user->department_id !== null
                    && (int) $folder->department_id !==
                        (int) $user->department_id
                ) {
                    throw new ForbiddenHttpException(
                        'You are not authorized to create a folder in this department.'
                    );
                }
            }
        }

        $folder->created_by = (int) $user->id;

        $now = date('Y-m-d H:i:s');

        $folder->created_at = $now;
        $folder->updated_at = $now;

        if (!$folder->validate()) {
            Yii::$app->response->statusCode = 422;

            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $folder->getErrors(),
            ];
        }

        if (!$folder->save(false)) {
            throw new \RuntimeException(
                'Unable to create folder.'
            );
        }

        Yii::$app->response->statusCode = 201;

        return [
            'success' => true,
            'message' => 'Folder created successfully.',
            'data' => $this->serializeFolder($folder),
        ];
    }

    /**
     * PUT /api/v1/folders/{id}
     */
    public function actionUpdate(int $id): array
    {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        $folder = Folder::findOne($id);

        if ($folder === null) {
            throw new NotFoundHttpException(
                'Folder not found.'
            );
        }

        $authorizationService = Yii::$container->get(
            AuthorizationService::class
        );

        /*
         * Critical authorization check.
         */
        if (!$authorizationService->canManageFolder(
            $user,
            $folder
        )) {
            throw new ForbiddenHttpException(
                'You are not authorized to update this folder.'
            );
        }

        $data = Yii::$app->request->bodyParams;

        if (empty($data)) {
            throw new BadRequestHttpException(
                'No fields were provided for update.'
            );
        }

        /*
         * Employees should not be able to move folders between
         * arbitrary organizational locations/departments.
         */
        if (
            array_key_exists('location_id', $data)
            || array_key_exists('department_id', $data)
            || array_key_exists('owner_user_id', $data)
        ) {
            if (!$user->hasGlobalAccess()) {
                throw new ForbiddenHttpException(
                    'You are not authorized to change folder ownership or organizational scope.'
                );
            }
        }

        if (array_key_exists('name', $data)) {
            $name = trim(
                (string) $data['name']
            );

            if ($name === '') {
                throw new BadRequestHttpException(
                    'Folder name cannot be empty.'
                );
            }

            $folder->name = $name;
        }

        if (array_key_exists('folder_type', $data)) {
            $folder->folder_type = (string) $data['folder_type'];
        }

        if (array_key_exists('parent_id', $data)) {
            $newParentId = $this->nullableInt(
                $data['parent_id']
            );

            /*
             * A folder cannot be its own parent.
             */
            if (
                $newParentId !== null
                && $newParentId === (int) $folder->id
            ) {
                throw new BadRequestHttpException(
                    'A folder cannot be its own parent.'
                );
            }

            if ($newParentId !== null) {
                $newParent = Folder::findOne(
                    $newParentId
                );

                if ($newParent === null) {
                    throw new BadRequestHttpException(
                        'Parent folder not found.'
                    );
                }

                if (!$authorizationService->canManageFolder(
                    $user,
                    $newParent
                )) {
                    throw new ForbiddenHttpException(
                        'You are not authorized to move the folder into the selected parent.'
                    );
                }

                /*
                 * Prevent simple circular hierarchy:
                 * moving folder A under one of A's descendants.
                 */
                if ($this->isDescendantOf(
                    $newParent,
                    $folder
                )) {
                    throw new BadRequestHttpException(
                        'Cannot move a folder inside its own descendant.'
                    );
                }

                /*
                 * Keep organizational scope consistent with
                 * the destination parent.
                 */
                $folder->location_id =
                    $newParent->location_id;

                $folder->department_id =
                    $newParent->department_id;
            }

            $folder->parent_id = $newParentId;
        }

        if (array_key_exists('location_id', $data)) {
            $folder->location_id = $this->nullableInt(
                $data['location_id']
            );
        }

        if (array_key_exists('department_id', $data)) {
            $folder->department_id = $this->nullableInt(
                $data['department_id']
            );
        }

        if (array_key_exists('owner_user_id', $data)) {
            $folder->owner_user_id = $this->nullableInt(
                $data['owner_user_id']
            );
        }

        $folder->updated_at = date(
            'Y-m-d H:i:s'
        );

        if (!$folder->validate()) {
            Yii::$app->response->statusCode = 422;

            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $folder->getErrors(),
            ];
        }

        if (!$folder->save(false)) {
            throw new \RuntimeException(
                'Unable to update folder.'
            );
        }

        return [
            'success' => true,
            'message' => 'Folder updated successfully.',
            'data' => $this->serializeFolder($folder),
        ];
    }

    /**
     * DELETE /api/v1/folders/{id}
     */
    public function actionDelete(int $id): array
    {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        $folder = Folder::findOne($id);

        if ($folder === null) {
            throw new NotFoundHttpException(
                'Folder not found.'
            );
        }

        $authorizationService = Yii::$container->get(
            AuthorizationService::class
        );

        /*
         * Critical authorization check.
         */
        if (!$authorizationService->canManageFolder(
            $user,
            $folder
        )) {
            throw new ForbiddenHttpException(
                'You are not authorized to delete this folder.'
            );
        }

        if ($folder->getChildren()->exists()) {
            throw new BadRequestHttpException(
                'Cannot delete a folder that contains child folders.'
            );
        }

        if ($folder->getFiles()->exists()) {
            throw new BadRequestHttpException(
                'Cannot delete a folder that contains files.'
            );
        }

        if ($folder->delete() === false) {
            throw new \RuntimeException(
                'Unable to delete folder.'
            );
        }

        return [
            'success' => true,
            'message' => 'Folder deleted successfully.',
        ];
    }

    /**
     * Convert folder to API response.
     */
    private function serializeFolder(
        Folder $folder
    ): array {
        return [
            'id' => (int) $folder->id,

            'parent_id' => $folder->parent_id !== null
                ? (int) $folder->parent_id
                : null,

            'location_id' => $folder->location_id !== null
                ? (int) $folder->location_id
                : null,

            'department_id' => $folder->department_id !== null
                ? (int) $folder->department_id
                : null,

            'owner_user_id' => $folder->owner_user_id !== null
                ? (int) $folder->owner_user_id
                : null,

            'name' => $folder->name,

            'folder_type' => $folder->folder_type,

            'created_by' => (int) $folder->created_by,

            'created_at' => $folder->created_at,

            'updated_at' => $folder->updated_at,

            'children_count' =>
                $folder->getChildren()->count(),

            'files_count' =>
                $folder->getFiles()->count(),
        ];
    }

    /**
     * Check whether $candidate is a descendant of $folder.
     */
    private function isDescendantOf(
        Folder $candidate,
        Folder $folder
    ): bool {
        $current = $candidate;

        $visited = [];

        while ($current->parent_id !== null) {
            $currentId = (int) $current->id;

            if (isset($visited[$currentId])) {
                return true;
            }

            $visited[$currentId] = true;

            if (
                (int) $current->parent_id ===
                (int) $folder->id
            ) {
                return true;
            }

            $parent = Folder::findOne(
                (int) $current->parent_id
            );

            if ($parent === null) {
                return false;
            }

            $current = $parent;
        }

        return false;
    }

    /**
     * Convert an optional value to nullable integer.
     */
    private function nullableInt(
        mixed $value
    ): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        /*
         * is_numeric() accepts values such as 1.5.
         * Reject those because this method is specifically
         * intended for integer IDs.
         */
        if (
            !is_int($value)
            && !ctype_digit((string) $value)
        ) {
            throw new BadRequestHttpException(
                'Expected an integer value.'
            );
        }

        return (int) $value;
    }
}