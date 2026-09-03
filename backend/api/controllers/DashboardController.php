<?php

declare(strict_types=1);

namespace api\controllers;

use common\models\File;
use common\models\FileShare;
use common\models\Folder;
use common\models\User;
use common\services\DataScopeService;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\web\Controller;
use yii\web\UnauthorizedHttpException;

final class DashboardController extends Controller
{
    public $enableCsrfValidation = false;

    private const STORAGE_QUOTA_BYTES = 10737418240; // 10 GB

    public function behaviors(): array
    {
        return [
            'authenticator' => [
                'class' => HttpBearerAuth::class,
            ],
        ];
    }

    /**
     * GET /api/v1/dashboard
     */
    public function actionIndex(): array
    {
        $user = Yii::$app->user->identity;

        if (!$user instanceof User) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        /** @var DataScopeService $dataScopeService */
        $dataScopeService = Yii::$container->get(
            DataScopeService::class
        );

        $visibleFiles = $dataScopeService->getVisibleFiles($user);
        $visibleFolders = $dataScopeService->getVisibleFolders($user);

        $storageUsedBytes =
            $dataScopeService->getStorageUsedBytes($visibleFiles);

        $sharedWithMe = 0;

        $shares = FileShare::find()
            ->where([
                'shared_with_user_id' => (int) $user->id,
            ])
            ->with('file')
            ->all();

        foreach ($shares as $share) {
            $file = $share->file;

            if (!$file instanceof File) {
                continue;
            }

            if ($file->status !== File::STATUS_ACTIVE) {
                continue;
            }

            if (!$share->isActive()) {
                continue;
            }

            if (!$this->fileIsInList($file, $visibleFiles)) {
                continue;
            }

            $sharedWithMe++;
        }

        /*
         * Count only files that are actually visible to the
         * authenticated user.
         *
         * Do not use $folder->getFiles()->count() here because
         * that would count files the user is not authorized to see.
         */
        $visibleFileCountsByFolder = [];

        foreach ($visibleFiles as $file) {
            if (!$file instanceof File || $file->folder_id === null) {
                continue;
            }

            $folderId = (int) $file->folder_id;

            if (!isset($visibleFileCountsByFolder[$folderId])) {
                $visibleFileCountsByFolder[$folderId] = 0;
            }

            $visibleFileCountsByFolder[$folderId]++;
        }

        usort(
            $visibleFolders,
            static function (Folder $a, Folder $b) use (
                $visibleFileCountsByFolder
            ): int {
                $aCount =
                    $visibleFileCountsByFolder[(int) $a->id] ?? 0;

                $bCount =
                    $visibleFileCountsByFolder[(int) $b->id] ?? 0;

                if ($aCount === $bCount) {
                    return (int) $b->id <=> (int) $a->id;
                }

                return $bCount <=> $aCount;
            }
        );

        $dashboardFolders = array_map(
            static function (Folder $folder) use (
                $visibleFileCountsByFolder
            ): array {
                return [
                    'id' => (int) $folder->id,
                    'name' => (string) $folder->name,
                    'filesCount' =>
                        (int) (
                            $visibleFileCountsByFolder[(int) $folder->id]
                            ?? 0
                        ),
                ];
            },
            array_slice($visibleFolders, 0, 5)
        );

        $recentFiles = array_map(
            static function (File $file): array {
                return [
                    'id' => (int) $file->id,
                    'originalName' => (string) $file->original_name,
                    'mimeType' => (string) $file->mime_type,
                    'sizeBytes' => (int) $file->size_bytes,
                    'uploadedAt' => (string) $file->uploaded_at,
                ];
            },
            array_slice($visibleFiles, 0, 5)
        );

        return [
            'success' => true,
            'data' => [
                'user' => [
                    'id' => (int) $user->id,
                    'name' => (string) $user->name,
                ],

                'scope' => [
                    'type' => $dataScopeService->getScopeType($user),
                    'label' => $dataScopeService->getScopeLabel($user),
                    'locationId' => $user->location_id !== null
                        ? (int) $user->location_id
                        : null,
                    'departmentId' => $user->department_id !== null
                        ? (int) $user->department_id
                        : null,
                ],

                'stats' => [
                    'totalFiles' => count($visibleFiles),
                    'totalFolders' => count($visibleFolders),
                    'storageUsedBytes' => $storageUsedBytes,
                    'storageQuotaBytes' => self::STORAGE_QUOTA_BYTES,
                    'sharedWithMe' => $sharedWithMe,
                ],

                'folders' => $dashboardFolders,
                'recentFiles' => $recentFiles,
            ],
        ];
    }

    /**
     * Check whether a file exists in the already-authorized result.
     *
     * @param File[] $files
     */
    private function fileIsInList(File $target, array $files): bool
    {
        foreach ($files as $file) {
            if (
                $file instanceof File
                && (int) $file->id === (int) $target->id
            ) {
                return true;
            }
        }

        return false;
    }
}
