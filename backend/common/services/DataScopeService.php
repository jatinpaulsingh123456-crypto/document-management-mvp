<?php

declare(strict_types=1);

namespace common\services;

use common\models\File;
use common\models\Folder;
use common\models\User;

final class DataScopeService
{
    public function __construct(
        private readonly AuthorizationService $authorizationService
    ) {
    }

    /**
     * Return the dashboard scope name for the current role.
     */
    public function getScopeType(User $user): string
    {
        if ($user->hasGlobalAccess()) {
            return 'global';
        }

        if ($user->isDepartmentHead()) {
            return 'department';
        }

        if ($user->isEmployee()) {
            return 'employee';
        }

        if ($user->isAuditor()) {
            return 'assigned';
        }

        return 'none';
    }

    /**
     * Return human-readable scope label.
     */
    public function getScopeLabel(User $user): string
    {
        if ($user->hasGlobalAccess()) {
            return 'Organization-wide';
        }

        if ($user->isDepartmentHead()) {
            return 'Department and location';
        }

        if ($user->isEmployee()) {
            return 'My files and shared files';
        }

        if ($user->isAuditor()) {
            return 'Assigned audit scope';
        }

        return 'No access';
    }

    /**
     * Files visible to the user according to backend authorization.
     *
     * The authorization service remains the security boundary.
     *
     * @return File[]
     */
    public function getVisibleFiles(User $user): array
    {
        $files = File::find()
            ->where([
                'status' => File::STATUS_ACTIVE,
            ])
            ->orderBy([
                'id' => SORT_DESC,
            ])
            ->all();

        return array_values(
            array_filter(
                $files,
                fn (File $file): bool =>
                    $this->authorizationService->canViewFile($user, $file)
            )
        );
    }

    /**
     * Folders visible to the user according to backend authorization.
     *
     * @return Folder[]
     */
    public function getVisibleFolders(User $user): array
    {
        $folders = Folder::find()
            ->orderBy([
                'id' => SORT_DESC,
            ])
            ->all();

        return array_values(
            array_filter(
                $folders,
                fn (Folder $folder): bool =>
                    $this->authorizationService->canViewFolder($user, $folder)
            )
        );
    }

    /**
     * Storage used by visible active files.
     */
    public function getStorageUsedBytes(array $files): int
    {
        $total = 0;

        foreach ($files as $file) {
            if ($file instanceof File) {
                $total += (int) $file->size_bytes;
            }
        }

        return $total;
    }
}
