<?php

declare(strict_types=1);

namespace api\controllers;

use common\models\AuditLog;

use common\models\File;
use common\models\FileShare;
use common\models\Folder;
use common\models\User;
use common\services\AuthorizationService;
use common\services\FileUploadService;
use common\storage\StorageInterface;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;
use yii\web\UnauthorizedHttpException;
use yii\web\UploadedFile;

class FileController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * Bearer-token authentication for all file endpoints.
     */
    public function behaviors(): array
    {
        return [
            'authenticator' => [
                'class' => HttpBearerAuth::class,
            ],
        ];
    }

    /**
     * GET /api/v1/shared-files
     *
     * Return active files explicitly shared with the
     * currently authenticated user.
     */
    public function actionSharedFiles(): array
    {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        $now = date('Y-m-d H:i:s');

        $rows = FileShare::find()
            ->alias('fs')
            ->select([
                'f.*',
                'fs.id AS share_id',
                'fs.permission AS permission',
                'fs.expires_at AS expires_at',
                'fs.created_at AS share_created_at',
                'fs.shared_by_user_id AS shared_by_user_id',
                'u.name AS shared_by_name',
                'u.email AS shared_by_email',
            ])
            ->innerJoin(
                ['f' => File::tableName()],
                'f.id = fs.file_id'
            )
            ->innerJoin(
                ['u' => \common\models\User::tableName()],
                'u.id = fs.shared_by_user_id'
            )
            ->where([
                'fs.shared_with_user_id' => (int) $user->id,
                'f.status' => File::STATUS_ACTIVE,
            ])
            ->andWhere([
                'or',
                ['fs.expires_at' => null],
                ['>', 'fs.expires_at', $now],
            ])
            ->orderBy([
                'fs.created_at' => SORT_DESC,
            ])
            ->asArray()
            ->all();

        return [
            'success' => true,
            'data' => array_map(
                static function (array $row): array {
                    return [
                        'id' => (int) $row['id'],
                        'folder_id' => (int) $row['folder_id'],
                        'uploader_id' => (int) $row['uploader_id'],
                        'location_id' => $row['location_id'] !== null
                            ? (int) $row['location_id']
                            : null,
                        'department_id' => $row['department_id'] !== null
                            ? (int) $row['department_id']
                            : null,
                        'name' => $row['name'],
                        'original_name' => $row['original_name'],
                        'storage_key' => $row['storage_key'],
                        'mime_type' => $row['mime_type'],
                        'extension' => $row['extension'],
                        'size_bytes' => (int) $row['size_bytes'],
                        'description' => $row['description'],
                        'tags' => $row['tags'] !== null
                            ? json_decode($row['tags'], true)
                            : null,
                        'checksum' => $row['checksum'],
                        'status' => $row['status'],
                        'uploaded_at' => $row['uploaded_at'],
                        'updated_at' => $row['updated_at'],
                        'share' => [
                            'permission' => $row['permission'],
                            'expires_at' => $row['expires_at'],
                            'shared_at' => $row['share_created_at'],
                            'shared_by' => [
                                'id' => (int) $row['shared_by_user_id'],
                                'name' => $row['shared_by_name'],
                                'email' => $row['shared_by_email'],
                            ],
                        ],
                    ];
                },
                $rows
            ),
        ];
    }

    /**
     * GET /api/v1/my-shares
     *
     * Current active shares created by the authenticated user.
     */
    public function actionMyShares(): array
    {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        $now = date('Y-m-d H:i:s');

        $rows = FileShare::find()
            ->alias('fs')
            ->select([
                'fs.id AS share_id',
                'f.id AS id',
                'f.folder_id AS folder_id',
                'f.uploader_id AS uploader_id',
                'f.location_id AS location_id',
                'f.department_id AS department_id',
                'f.name AS name',
                'f.original_name AS original_name',
                'f.storage_key AS storage_key',
                'f.mime_type AS mime_type',
                'f.extension AS extension',
                'f.size_bytes AS size_bytes',
                'f.description AS description',
                'f.tags AS tags',
                'f.checksum AS checksum',
                'f.status AS status',
                'f.uploaded_at AS uploaded_at',
                'f.updated_at AS updated_at',
                'fs.shared_with_user_id AS shared_with_user_id',
                'fs.shared_by_user_id AS shared_by_user_id',
                'fs.permission AS permission',
                'fs.expires_at AS expires_at',
                'fs.created_at AS shared_at',
                'u.name AS shared_with_name',
                'u.email AS shared_with_email',
                'sb.name AS shared_by_name',
                'sb.email AS shared_by_email',
            ])
            ->innerJoin(
                ['f' => File::tableName()],
                'f.id = fs.file_id'
            )
            ->innerJoin(
                ['u' => User::tableName()],
                'u.id = fs.shared_with_user_id'
            )
            ->innerJoin(
                ['sb' => User::tableName()],
                'sb.id = fs.shared_by_user_id'
            )
            ->where([
                'fs.shared_by_user_id' => (int) $user->id,
                'f.status' => File::STATUS_ACTIVE,
            ])
            ->andWhere([
                'or',
                ['fs.expires_at' => null],
                ['>', 'fs.expires_at', $now],
            ])
            ->orderBy([
                'fs.created_at' => SORT_DESC,
                'fs.id' => SORT_DESC,
            ])
            ->asArray()
            ->all();

        return [
            'success' => true,
            'data' => array_map(
                static function (array $row): array {
                    return [
                        'id' => (int) $row['id'],
                        'folder_id' => (int) $row['folder_id'],
                        'uploader_id' => (int) $row['uploader_id'],
                        'location_id' => $row['location_id'] !== null
                            ? (int) $row['location_id']
                            : null,
                        'department_id' => $row['department_id'] !== null
                            ? (int) $row['department_id']
                            : null,
                        'name' => $row['name'],
                        'original_name' => $row['original_name'],
                        'storage_key' => $row['storage_key'],
                        'mime_type' => $row['mime_type'],
                        'extension' => $row['extension'],
                        'size_bytes' => (int) $row['size_bytes'],
                        'description' => $row['description'],
                        'tags' => $row['tags'] !== null
                            ? json_decode($row['tags'], true)
                            : null,
                        'checksum' => $row['checksum'],
                        'status' => $row['status'],
                        'uploaded_at' => $row['uploaded_at'],
                        'updated_at' => $row['updated_at'],
                        'share' => [
                            'id' => (int) $row['share_id'],
                            'permission' => $row['permission'],
                            'expires_at' => $row['expires_at'],
                            'shared_at' => $row['shared_at'],
                            'shared_by' => [
                                'id' => (int) $row['shared_by_user_id'],
                                'name' => (string) ($row['shared_by_name'] ?? ''),
                                'email' => (string) ($row['shared_by_email'] ?? ''),
                            ],
                            'shared_with' => [
                                'id' => (int) $row['shared_with_user_id'],
                                'name' => $row['shared_with_name'],
                                'email' => $row['shared_with_email'],
                            ],
                        ],
                    ];
                },
                $rows
            ),
        ];
    }

    /**
     * GET /api/v1/shared-history
     *
     * Permanent share/unshare history relevant to the user.
     */
    public function actionSharedHistory(): array
    {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        $rows = AuditLog::find()
            ->alias('al')
            ->leftJoin(
                ['f' => File::tableName()],
                'f.id = al.file_id'
            )
            ->where([
                'al.entity_type' => AuditLog::ENTITY_SHARE,
            ])
            ->orderBy([
                'al.created_at' => SORT_DESC,
                'al.id' => SORT_DESC,
            ])
            ->limit(200)
            ->all();

        $result = [];

        foreach ($rows as $row) {
            $metadata = $row->getMetadataArray() ?? [];

            $sharedById = isset($metadata['shared_by_user_id'])
                ? (int) $metadata['shared_by_user_id']
                : null;

            $sharedWithId = isset($metadata['shared_with_user_id'])
                ? (int) $metadata['shared_with_user_id']
                : null;

            if (
                $sharedById !== (int) $user->id
                && $sharedWithId !== (int) $user->id
            ) {
                continue;
            }

            $result[] = [
                'id' => (int) $row->id,
                'action' => $row->action,
                'status' => $row->action === AuditLog::ACTION_SHARE
                    ? 'shared'
                    : 'revoked',
                'created_at' => $row->created_at,

                'file' => [
                    'id' => $row->file_id !== null
                        ? (int) $row->file_id
                        : null,
                    'original_name' =>
                        $metadata['original_name'] ?? null,
                    'mime_type' =>
                        $metadata['mime_type'] ?? null,
                    'size_bytes' => isset($metadata['size_bytes'])
                        ? (int) $metadata['size_bytes']
                        : null,
                    'location_id' => isset($metadata['location_id'])
                        ? (int) $metadata['location_id']
                        : null,
                    'department_id' =>
                        isset($metadata['department_id'])
                            ? (int) $metadata['department_id']
                            : null,
                ],

                'shared_by' => [
                    'id' => $sharedById,
                ],

                'shared_with' => [
                    'id' => $sharedWithId,
                    'name' =>
                        $metadata['shared_with_name'] ?? null,
                    'email' =>
                        $metadata['shared_with_email'] ?? null,
                ],

                'permission' =>
                    $metadata['permission'] ?? null,
                'expires_at' =>
                    $metadata['expires_at'] ?? null,

                'performed_by_user_id' => $row->user_id !== null
                    ? (int) $row->user_id
                    : null,
            ];
        }

        return [
            'success' => true,
            'data' => $result,
        ];
    }

    /**
     * GET /api/v1/files
     *
     * Return only active files the current user may view.
     */
    public function actionIndex(): array
    {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        /** @var AuthorizationService $authorizationService */
        $authorizationService = Yii::$container->get(
            AuthorizationService::class
        );

        $query = File::find()
            ->where([
                'status' => File::STATUS_ACTIVE,
            ]);

        /*
         * Optional folder filter.
         *
         * /files              -> all files visible to the user
         * /files?folder_id=5  -> files inside folder 5
         */
        $folderId = Yii::$app->request->get('folder_id');

        if ($folderId !== null && $folderId !== '') {
            if (
                !ctype_digit((string) $folderId)
                || (int) $folderId <= 0
            ) {
                throw new BadRequestHttpException(
                    'folder_id must be a valid folder ID.'
                );
            }

            $query->andWhere([
                'folder_id' => (int) $folderId,
            ]);
        }

        $files = $query
            ->orderBy([
                'id' => SORT_DESC,
            ])
            ->all();

        /*
         * MVP implementation:
         *
         * Filter results through AuthorizationService.
         *
         * Later this can be converted into query-level filtering
         * for better performance when the dataset becomes large.
         */
        $visibleFiles = array_filter(
            $files,
            fn (File $file): bool =>
                $authorizationService->canViewFile(
                    $user,
                    $file
                )
        );

        return [
            'success' => true,
            'data' => array_values(
                array_map(
                    fn (File $file): array =>
                        $this->serializeFile($file),
                    $visibleFiles
                )
            ),
        ];
    }

    /**
     * GET /api/v1/files/{id}
     */
    public function actionView(int $id): array
    {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        $file = File::findOne($id);

        if (
            $file === null
            || $file->status !== File::STATUS_ACTIVE
        ) {
            throw new NotFoundHttpException(
                'File not found.'
            );
        }

        /** @var AuthorizationService $authorizationService */
        $authorizationService = Yii::$container->get(
            AuthorizationService::class
        );

        if (!$authorizationService->canViewFile(
            $user,
            $file
        )) {
            throw new ForbiddenHttpException(
                'You are not authorized to view this file.'
            );
        }

        return [
            'success' => true,
            'data' => $this->serializeFile($file),
        ];
    }

    /**
     * GET /api/v1/files/{id}/download
     *
     * Download the physical file.
     */
    /**
 * GET /api/v1/files/{id}/download
 *
 * Download the physical file.
 */
public function actionDownload(int $id)
    {
    $file = File::findOne($id);

    if ($file === null) {
        throw new NotFoundHttpException(
            'File not found.'
        );
    }

    if ($file->status !== File::STATUS_ACTIVE) {
        throw new NotFoundHttpException(
            'File is not available.'
        );
    }

    $user = \Yii::$app->user->identity;

    if ($user === null) {
        throw new UnauthorizedHttpException(
            'Authentication required.'
        );
    }

    /*
     * TODO:
     * Replace this uploader-only check with the same
     * hierarchy authorization used elsewhere once the
     * download authorization method is available.
     */
        /** @var AuthorizationService $authorizationService */
    $authorizationService = Yii::$container->get(
        AuthorizationService::class
    );

    if (!$authorizationService->canDownloadFile($user, $file)) {
        throw new ForbiddenHttpException(
            'You are not authorized to download this file.'
        );
    }
    /*
     * Resolve storage through Yii DI.
     */
    /** @var \common\storage\StorageInterface $storage */
    $storage = \Yii::$container->get(
        \common\storage\StorageInterface::class
    );

    if (!$storage->exists($file->storage_key)) {
        throw new NotFoundHttpException(
            'Physical file not found.'
        );
    }

    $path = $storage->path(
        $file->storage_key
    );

    /*
     * Force download using the original filename.
     */
    return \Yii::$app->response->sendFile(
        $path,
        $file->original_name,
        [
            'mimeType' => $file->mime_type
                ?: 'application/octet-stream',
            'inline' => false,
        ]
    );
}

    /**
     * POST /api/v1/files/check-duplicates
     *
     * Checks SHA-256 checksums against all active files.
     */
    public function actionCheckDuplicates(): array
    {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        $body = Yii::$app->request->bodyParams;
        $checksums = $body['checksums'] ?? null;

        if (!is_array($checksums)) {
            throw new BadRequestHttpException(
                'checksums must be an array.'
            );
        }

        $normalized = [];

        foreach ($checksums as $checksum) {
            $checksum = strtolower(trim((string) $checksum));

            if (!preg_match('/^[a-f0-9]{64}$/', $checksum)) {
                throw new BadRequestHttpException(
                    'Each checksum must be a valid SHA-256 hash.'
                );
            }

            $normalized[$checksum] = true;
        }

        $checksums = array_keys($normalized);

        if ($checksums === []) {
            return [
                'success' => true,
                'data' => [
                    'duplicates' => [],
                ],
            ];
        }

        $existing = File::find()
            ->select([
                'id',
                'checksum',
                'original_name',
                'folder_id',
                'uploader_id',
            ])
            ->where([
                'status' => File::STATUS_ACTIVE,
            ])
            ->andWhere([
                'checksum' => $checksums,
            ])
            ->asArray()
            ->all();

        return [
            'success' => true,
            'data' => [
                'duplicates' => array_map(
                    static function (array $file): array {
                        return [
                            'file_id' => (int) $file['id'],
                            'checksum' => $file['checksum'],
                            'original_name' => $file['original_name'],
                            'folder_id' => $file['folder_id'] !== null
                                ? (int) $file['folder_id']
                                : null,
                            'uploader_id' => $file['uploader_id'] !== null
                                ? (int) $file['uploader_id']
                                : null,
                        ];
                    },
                    $existing
                ),
            ],
        ];
    }

    /**
     * POST /api/v1/files
     *
     * Multipart upload.
     *
     * Required:
     * - file
     * - folder_id
     *
     * Optional:
     * - description
     * - tags
     */
    public function actionCreate(): array
    {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        $request = Yii::$app->request;

        $folderId = $this->nullableInt(
            $request->post('folder_id')
        );

        if ($folderId === null) {
            throw new BadRequestHttpException(
                'folder_id is required.'
            );
        }

        $folder = Folder::findOne(
            $folderId
        );

        if ($folder === null) {
            throw new BadRequestHttpException(
                'Folder not found.'
            );
        }

        $uploadedFile = UploadedFile::getInstanceByName(
            'file'
        );

        if ($uploadedFile === null) {
            throw new BadRequestHttpException(
                'No file was uploaded. Use multipart field "file".'
            );
        }

        $description = $request->post(
            'description'
        );

        if ($description !== null) {
            $description = trim(
                (string) $description
            );

            if ($description === '') {
                $description = null;
            }
        }

        $tags = $request->post(
            'tags'
        );

        if ($tags !== null) {
            $tags = trim(
                (string) $tags
            );

            if ($tags === '') {
                $tags = null;
            }
        }

        /** @var FileUploadService $uploadService */
        $uploadService = Yii::$container->get(
            FileUploadService::class
        );

        try {
            $file = $uploadService->upload(
                $user,
                $folder,
                $uploadedFile,
                $description,
                $tags
            );
        } catch (\RuntimeException $e) {
            if (
                str_contains(
                    strtolower($e->getMessage()),
                    'already exists'
                )
            ) {
                throw new \yii\web\ConflictHttpException(
                    $e->getMessage()
                );
            }

            throw new BadRequestHttpException(
                $e->getMessage(),
                0,
                $e
            );
        }

        Yii::$app->response->statusCode = 201;

        return [
            'success' => true,
            'message' => 'File uploaded successfully.',
            'data' => $this->serializeFile($file),
        ];
    }

    /**
     * PUT /api/v1/files/{id}
     *
     * Rename/update metadata.
     */
    public function actionUpdate(int $id): array
    {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        $file = File::findOne($id);

        if ($file === null) {
            throw new NotFoundHttpException(
                'File not found.'
            );
        }

        /** @var AuthorizationService $authorizationService */
        $authorizationService = Yii::$container->get(
            AuthorizationService::class
        );

        if (!$authorizationService->canEditFile(
            $user,
            $file
        )) {
            throw new ForbiddenHttpException(
                'You are not authorized to update this file.'
            );
        }

        $body = Yii::$app->request->bodyParams;

        if (empty($body)) {
            throw new BadRequestHttpException(
                'No fields were provided for update.'
            );
        }

        if (array_key_exists(
            'status',
            $body
        )) {
            throw new BadRequestHttpException(
                'Use the delete or restore endpoint to change file status.'
            );
        }

        if (array_key_exists(
            'storage_key',
            $body
        )) {
            throw new BadRequestHttpException(
                'Storage key cannot be changed.'
            );
        }

        /*
         * folder_id is intentionally excluded here.
         *
         * Moving files will get a dedicated endpoint.
         */
        $allowedFields = [
            'name',
            'original_name',
            'description',
            'tags',
        ];

        foreach ($body as $attribute => $value) {
            if (!in_array(
                $attribute,
                $allowedFields,
                true
            )) {
                throw new BadRequestHttpException(
                    "Field '{$attribute}' cannot be updated."
                );
            }

            if (
                $attribute === 'name'
                || $attribute === 'original_name'
            ) {
                $file->$attribute = trim(
                    (string) $value
                );
            } else {
                $file->$attribute = $value;
            }
        }

        if (!$file->validate()) {
            Yii::$app->response->statusCode = 422;

            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $file->getErrors(),
            ];
        }

        $file->updated_at = date(
            'Y-m-d H:i:s'
        );

        if (!$file->save(false)) {
            throw new ServerErrorHttpException(
                'Failed to update file.'
            );
        }

        return [
            'success' => true,
            'message' => 'File updated successfully.',
            'data' => $this->serializeFile($file),
        ];
    }

    /**
 * PUT /api/v1/files/{id}/move
 *
 * Move a file to another folder.
 */
public function actionMove(int $id): array
    {
    $user = Yii::$app->user->identity;

    if ($user === null) {
        throw new UnauthorizedHttpException(
            'Authentication required.'
        );
    }

    $file = File::findOne($id);

    if ($file === null) {
        throw new NotFoundHttpException(
            'File not found.'
        );
    }

    if ($file->status !== File::STATUS_ACTIVE) {
        throw new NotFoundHttpException(
            'File is not available.'
        );
    }

    /** @var AuthorizationService $authorizationService */
    $authorizationService = Yii::$container->get(
        AuthorizationService::class
    );

    if (!$authorizationService->canEditFile(
        $user,
        $file
    )) {
        throw new ForbiddenHttpException(
            'You are not authorized to move this file.'
        );
    }

    $body = Yii::$app->request->bodyParams;

    if (!isset($body['folder_id'])) {
        throw new BadRequestHttpException(
            'folder_id is required.'
        );
    }

    $folderId = $this->nullableInt(
        $body['folder_id']
    );

    if ($folderId === null) {
        throw new BadRequestHttpException(
            'folder_id must be a valid folder ID.'
        );
    }

    $folder = Folder::findOne($folderId);

    if ($folder === null) {
        throw new NotFoundHttpException(
            'Destination folder not found.'
        );
    }

    /*
     * The user must also have permission to upload/manage
     * content in the destination folder.
     */
    if (!$authorizationService->canUploadToFolder(
        $user,
        $folder
    )) {
        throw new ForbiddenHttpException(
            'You are not authorized to move files into this folder.'
        );
    }

    if ((int) $file->folder_id === (int) $folder->id) {
        throw new BadRequestHttpException(
            'File is already in this folder.'
        );
    }

    $file->folder_id = $folder->id;

    /*
     * Update organizational metadata to match destination.
     */
    $file->location_id = $folder->location_id;
    $file->department_id = $folder->department_id;
    $file->updated_at = date('Y-m-d H:i:s');

    if (!$file->save(false)) {
        throw new ServerErrorHttpException(
            'Failed to move file.'
        );
    }

    return [
        'success' => true,
        'message' => 'File moved successfully.',
        'data' => $this->serializeFile($file),
        ];
    }

    /**
     * DELETE /api/v1/files/{id}
     *
     * Soft-delete file metadata.
     */
    public function actionDelete(int $id): array
    {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        $file = File::findOne($id);

        if ($file === null) {
            throw new NotFoundHttpException(
                'File not found.'
            );
        }

        /** @var AuthorizationService $authorizationService */
        $authorizationService = Yii::$container->get(
            AuthorizationService::class
        );

        if (!$authorizationService->canManageFile(
            $user,
            $file
        )) {
            throw new ForbiddenHttpException(
                'You are not authorized to delete this file.'
            );
        }

        if ($file->status === File::STATUS_DELETED) {
            throw new BadRequestHttpException(
                'File is already deleted.'
            );
        }

        $file->status = File::STATUS_DELETED;
        $file->updated_at = date(
            'Y-m-d H:i:s'
        );

        if (!$file->save(false)) {
            throw new ServerErrorHttpException(
                'Failed to delete file.'
            );
        }

        return [
            'success' => true,
            'message' => 'File deleted successfully.',
        ];
    }

    /**
     * GET /api/v1/files/trash
     */
    public function actionTrash(): array
    {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        /** @var AuthorizationService $authorizationService */
        $authorizationService = Yii::$container->get(
            AuthorizationService::class
        );

        $files = File::find()
            ->where([
                'status' => File::STATUS_DELETED,
            ])
            ->orderBy([
                'id' => SORT_DESC,
            ])
            ->all();

        $visibleFiles = array_filter(
            $files,
            fn (File $file): bool =>
                $authorizationService->canViewFile(
                    $user,
                    $file
                )
        );

        return [
            'success' => true,
            'data' => array_values(
                array_map(
                    fn (File $file): array =>
                        $this->serializeFile($file),
                    $visibleFiles
                )
            ),
        ];
    }

    /**
     * PUT /api/v1/files/{id}/restore
     */
    public function actionRestore(int $id): array
    {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        $file = File::findOne($id);

        if ($file === null) {
            throw new NotFoundHttpException(
                'File not found.'
            );
        }

        /** @var AuthorizationService $authorizationService */
        $authorizationService = Yii::$container->get(
            AuthorizationService::class
        );

        if (!$authorizationService->canManageFile(
            $user,
            $file
        )) {
            throw new ForbiddenHttpException(
                'You are not authorized to restore this file.'
            );
        }

        if ($file->status !== File::STATUS_DELETED) {
            throw new BadRequestHttpException(
                'File is not deleted.'
            );
        }

        $file->status = File::STATUS_ACTIVE;
        $file->updated_at = date(
            'Y-m-d H:i:s'
        );

        if (!$file->save(false)) {
            throw new ServerErrorHttpException(
                'Failed to restore file.'
            );
        }

        return [
            'success' => true,
            'message' => 'File restored successfully.',
            'data' => $this->serializeFile($file),
        ];
    }
        /**
     * POST /api/v1/files/{id}/share
     *
     * Share a file with another user.
     *
     * JSON:
     * {
     *   "user_id": 2,
     *   "permission": "read",
     *   "expires_at": "2026-09-30 23:59:59"
     * }
     */
    public function actionShare(int $id): array
    {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        $file = File::findOne($id);

        if ($file === null || $file->status !== File::STATUS_ACTIVE) {
            throw new NotFoundHttpException(
                'File not found.'
            );
        }

        /** @var AuthorizationService $authorizationService */
        $authorizationService = Yii::$container->get(
            AuthorizationService::class
        );

        if (!$authorizationService->canManageFile($user, $file)) {
            throw new ForbiddenHttpException(
                'You are not authorized to share this file.'
            );
        }

        $body = Yii::$app->request->bodyParams;

        if (!isset($body['user_id'])) {
            throw new BadRequestHttpException(
                'user_id is required.'
            );
        }

        $sharedWithUserId = $this->nullableInt(
            $body['user_id']
        );

        if ($sharedWithUserId === null) {
            throw new BadRequestHttpException(
                'user_id must be a valid user ID.'
            );
        }

        if ((int) $sharedWithUserId === (int) $user->id) {
            throw new BadRequestHttpException(
                'You cannot share a file with yourself.'
            );
        }

        $sharedWithUser = \common\models\User::findOne([
            'id' => $sharedWithUserId,
            'status' => \common\models\User::STATUS_ACTIVE,
        ]);

        if ($sharedWithUser === null) {
            throw new NotFoundHttpException(
                'Target user not found.'
            );
        }

        $permission = $body['permission']
            ?? FileShare::PERMISSION_READ;

        $allowedPermissions = [
            FileShare::PERMISSION_READ,
            FileShare::PERMISSION_WRITE,
            FileShare::PERMISSION_MANAGE,
        ];

        if (!in_array($permission, $allowedPermissions, true)) {
            throw new BadRequestHttpException(
                'permission must be read, write, or manage.'
            );
        }

        $expiresAt = $body['expires_at'] ?? null;

        if ($expiresAt !== null) {
            $expiresAt = trim((string) $expiresAt);

            if ($expiresAt === '') {
                $expiresAt = null;
            }
        }

        if ($expiresAt !== null && strtotime($expiresAt) === false) {
            throw new BadRequestHttpException(
                'expires_at must be a valid date/time.'
            );
        }

        /*
         * Prevent duplicate active shares.
         */
        $existingShare = FileShare::find()
            ->where([
                'file_id' => $file->id,
                'shared_with_user_id' => $sharedWithUserId,
            ])
            ->one();

        if ($existingShare !== null) {
            if ($existingShare->isActive()) {
                throw new BadRequestHttpException(
                    'An active share already exists for this user.'
                );
            }

            /*
             * Reuse an expired share.
             */
            $share = $existingShare;
        } else {
            $share = new FileShare();
        }

        $share->file_id = $file->id;
        $share->shared_with_user_id = $sharedWithUserId;
        $share->shared_by_user_id = $user->id;
        $share->permission = $permission;
        $share->expires_at = $expiresAt;
        $share->created_at = date('Y-m-d H:i:s');

        if (!$share->validate()) {
            Yii::$app->response->statusCode = 422;

            return [
                'success' => false,
                'message' => 'Share validation failed.',
                'errors' => $share->getErrors(),
            ];
        }

        if (!$share->save(false)) {
            throw new ServerErrorHttpException(
                'Failed to create file share.'
            );
        }

        /*
         * Permanent share history.
         *
         * file_shares contains the current active relationship.
         * audit_logs keeps the historical record.
         */
        $audit = new AuditLog();

        $audit->user_id = (int) $user->id;
        $audit->action = AuditLog::ACTION_SHARE;
        $audit->entity_type = AuditLog::ENTITY_SHARE;
        $audit->entity_id = (int) $share->id;
        $audit->file_id = (int) $file->id;
        $audit->folder_id = $file->folder_id !== null
            ? (int) $file->folder_id
            : null;

        $audit->ip_address = Yii::$app->request->userIP;

        $userAgent = Yii::$app->request->userAgent;

        $audit->user_agent = $userAgent !== null
            ? substr($userAgent, 0, 1000)
            : null;

        $sharedWithUser = User::findOne(
            (int) $share->shared_with_user_id
        );

        $audit->setMetadataArray([
            'share_id' => (int) $share->id,
            'file_id' => (int) $file->id,
            'original_name' => $file->original_name,
            'shared_by_user_id' => (int) $share->shared_by_user_id,
            'shared_with_user_id' => (int) $share->shared_with_user_id,
            'shared_with_name' => $sharedWithUser?->name,
            'shared_with_email' => $sharedWithUser?->email,
            'permission' => $share->permission,
            'expires_at' => $share->expires_at,
            'location_id' => $file->location_id !== null
                ? (int) $file->location_id
                : null,
            'department_id' => $file->department_id !== null
                ? (int) $file->department_id
                : null,
            'size_bytes' => $file->size_bytes !== null
                ? (int) $file->size_bytes
                : null,
            'mime_type' => $file->mime_type,
        ]);

        $audit->created_at = date('Y-m-d H:i:s');

        if (!$audit->save()) {
            throw new ServerErrorHttpException(
                'File share was created, but share history could not be recorded.'
            );
        }

        Yii::$app->response->statusCode = 201;

        return [
            'success' => true,
            'message' => 'File shared successfully.',
            'data' => $this->serializeShare($share),
        ];
    }

    /**
     * GET /api/v1/files/{id}/shares
     *
     * List shares for a file.
     */
    public function actionShares(int $id): array
    {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        $file = File::findOne($id);

        if ($file === null) {
            throw new NotFoundHttpException(
                'File not found.'
            );
        }

        /** @var AuthorizationService $authorizationService */
        $authorizationService = Yii::$container->get(
            AuthorizationService::class
        );

        if (!$authorizationService->canViewFile($user, $file)) {
            throw new ForbiddenHttpException(
                'You are not authorized to view file shares.'
            );
        }

        $shares = FileShare::find()
            ->where([
                'file_id' => $file->id,
            ])
            ->orderBy([
                'id' => SORT_DESC,
            ])
            ->all();

        return [
            'success' => true,
            'data' => array_map(
                fn (FileShare $share): array =>
                    $this->serializeShare($share),
                $shares
            ),
        ];
    }

    /**
     * DELETE /api/v1/files/{id}/shares/{shareId}
     *
     * Revoke a file share.
     */
    public function actionRevokeShare(
        int $id,
        int $shareId
    ): array {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        $file = File::findOne($id);

        if ($file === null) {
            throw new NotFoundHttpException(
                'File not found.'
            );
        }

        /** @var AuthorizationService $authorizationService */
        $authorizationService = Yii::$container->get(
            AuthorizationService::class
        );

        if (!$authorizationService->canManageFile($user, $file)) {
            throw new ForbiddenHttpException(
                'You are not authorized to revoke file shares.'
            );
        }

        $share = FileShare::findOne([
            'id' => $shareId,
            'file_id' => $file->id,
        ]);

        if ($share === null) {
            throw new NotFoundHttpException(
                'Share not found.'
            );
        }

        /*
         * Save the permanent revoke history BEFORE deleting
         * the active share record.
         */
        $sharedWithUser = User::findOne(
            (int) $share->shared_with_user_id
        );

        $audit = new AuditLog();

        $audit->user_id = (int) $user->id;
        $audit->action = AuditLog::ACTION_UNSHARE;
        $audit->entity_type = AuditLog::ENTITY_SHARE;
        $audit->entity_id = (int) $share->id;
        $audit->file_id = (int) $file->id;
        $audit->folder_id = $file->folder_id !== null
            ? (int) $file->folder_id
            : null;

        $audit->ip_address = Yii::$app->request->userIP;

        $userAgent = Yii::$app->request->userAgent;

        $audit->user_agent = $userAgent !== null
            ? substr($userAgent, 0, 1000)
            : null;

        $audit->setMetadataArray([
            'share_id' => (int) $share->id,
            'file_id' => (int) $file->id,
            'original_name' => $file->original_name,
            'shared_by_user_id' => (int) $share->shared_by_user_id,
            'shared_with_user_id' => (int) $share->shared_with_user_id,
            'shared_with_name' => $sharedWithUser?->name,
            'shared_with_email' => $sharedWithUser?->email,
            'permission' => $share->permission,
            'expires_at' => $share->expires_at,
            'location_id' => $file->location_id !== null
                ? (int) $file->location_id
                : null,
            'department_id' => $file->department_id !== null
                ? (int) $file->department_id
                : null,
            'size_bytes' => $file->size_bytes !== null
                ? (int) $file->size_bytes
                : null,
            'mime_type' => $file->mime_type,
        ]);

        $audit->created_at = date('Y-m-d H:i:s');

        if (!$audit->save()) {
            throw new ServerErrorHttpException(
                'Share could not be revoked because its history could not be recorded.'
            );
        }

        if (!$share->delete()) {
            throw new ServerErrorHttpException(
                'Failed to revoke file share.'
            );
        }

        return [
            'success' => true,
            'message' => 'File share revoked successfully.',
        ];
    }


    /**
     * Serialize a file share.
     */
    private function serializeShare(
        FileShare $share
    ): array {
        return [
            'id' => (int) $share->id,
            'file_id' => (int) $share->file_id,
            'shared_with_user_id' =>
                (int) $share->shared_with_user_id,
            'shared_by_user_id' =>
                (int) $share->shared_by_user_id,
            'permission' => $share->permission,
            'expires_at' => $share->expires_at,
            'active' => $share->isActive(),
            'created_at' => $share->created_at,
        ];
    }

    /**
     * Convert value to nullable integer.
     */
    private function nullableInt($value): ?int
    {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        if (
            !is_numeric($value)
            || (int) $value < 0
        ) {
            throw new BadRequestHttpException(
                'Value must be a valid non-negative integer.'
            );
        }

        return (int) $value;
    }

    /**
     * Serialize file metadata.
     */
    private function serializeFile(
        File $file
    ): array {
        return [
            'id' => (int) $file->id,

            'folder_id' => $file->folder_id !== null
                ? (int) $file->folder_id
                : null,

            'uploader_id' => $file->uploader_id !== null
                ? (int) $file->uploader_id
                : null,

            'location_id' => $file->location_id !== null
                ? (int) $file->location_id
                : null,

            'department_id' => $file->department_id !== null
                ? (int) $file->department_id
                : null,

            'name' => $file->name,
            'original_name' => $file->original_name,

            /*
             * Internal storage_key is currently returned because
             * the existing API already exposes it.
             *
             * Before production, consider removing this field
             * from client-facing responses.
             */
            'storage_key' => $file->storage_key,

            'mime_type' => $file->mime_type,
            'extension' => $file->extension,

            'size_bytes' => $file->size_bytes !== null
                ? (int) $file->size_bytes
                : null,

            'description' => $file->description,
            'tags' => $file->tags,
            'checksum' => $file->checksum,
            'status' => $file->status,
            'uploaded_at' => $file->uploaded_at,
            'updated_at' => $file->updated_at,
        ];
    }
}