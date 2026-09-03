<?php

declare(strict_types=1);

namespace common\services;

use common\models\File;
use common\models\Folder;
use common\models\User;

final class AuthorizationService
{
    /**
     * Check whether the user can view a folder.
     */
    public function canViewFolder(
        User $user,
        Folder $folder
    ): bool {
        if ($user->hasGlobalAccess()){
            return true;
        }

        if ($user->isAuditor()) {
            return $this->isWithinAssignedScope($user, $folder);
        }

        if ($user->isEmployee()) {
            return $this->isOwnFolder($user, $folder)
                || $this->isWithinEmployeeFolderScope($user, $folder);
        }

        if ($user->isDepartmentHead()) {
            return $this->isWithinManagerScope($user, $folder);
        }

        return false;
    }

    /**
     * Check whether the user can upload into a folder.
     */
    public function canUploadToFolder(
        User $user,
        Folder $folder
    ): bool {
        if ($user->hasGlobalAccess()){
            return true;
        }

        if ($user->isAuditor()) {
            return false;
        }

        /*
         * Employee:
         * - own user folder
         * - department/location folders belonging to their scope
         * - custom/nested folders inside their scope
         */
        if ($user->isEmployee()) {
            return $this->isOwnFolder($user, $folder)
                    || $this->isWithinEmployeeFolderScope($user, $folder);
        }

        /*
         * Department / Location Head.
         */
        if ($user->isDepartmentHead()) {
            return $this->isWithinManagerScope($user, $folder);
        }

        return false;
    }

    /**
     * Check whether the user can manage a folder.
     */
    public function canManageFolder(
        User $user,
        Folder $folder
    ): bool {
        if ($user->hasGlobalAccess()){
            return true;
        }

        if ($user->isAuditor()) {
            return false;
        }

        /*
         * Employee:
         * Own folders and folders within their organizational scope.
         */
        if ($user->isEmployee()) {
            return $this->isOwnFolder($user, $folder);
        }

        /*
         * Department / Location Head.
         */
        if ($user->isDepartmentHead()) {
            return $this->isWithinManagerScope($user, $folder);
        }

        return false;
    }

    /**
     * Check whether the user can view a file.
     */
    public function canViewFile(
        User $user,
        File $file
    ): bool {
        if ($user->hasGlobalAccess()){
            return true;
        }

        /*
         * Employee:
         * Own files + explicitly shared files.
         */
        if ($user->isEmployee()) {
            return
                (int) $file->uploader_id === (int) $user->id
                || $this->hasActiveShare($user, $file);
        }

        /*
         * Auditor:
         * Read-only access within assigned scope.
         */
        if ($user->isAuditor()) {
            return $this->isWithinFileScope($user, $file);
        }

        /*
         * Department / Location Head.
         */
        if ($user->isDepartmentHead()) {
            return $this->isWithinManagerFileScope(
                $user,
                $file
            );
        }

        return false;
    }

    /**
     * Check whether the user can download a file.
     */
    public function canDownloadFile(
        User $user,
        File $file
    ): bool {
        return $this->canViewFile($user, $file);
    }

    /**
     * Check whether the user can edit a file.
     *
     * Employees may edit:
     * - their own files
     * - files explicitly shared with write/manage permission
     */
    public function canEditFile(
        User $user,
        File $file
    ): bool {
        if ($user->hasGlobalAccess()){
            return true;
        }

        if ($user->isAuditor()) {
            return false;
        }

        /*
         * Employee:
         * Own files + explicitly shared write/manage files.
         */
        if ($user->isEmployee()) {
            if (
                (int) $file->uploader_id ===
                (int) $user->id
            ) {
                return true;
            }

            foreach ($file->shares as $share) {
                if (
                    (int) $share->shared_with_user_id ===
                        (int) $user->id
                    && $share->isActive()
                    && $share->canWrite()
                ) {
                    return true;
                }
            }

            return false;
        }

        /*
         * Department / Location Head.
         */
        if ($user->isDepartmentHead()) {
            return $this->isWithinManagerFileScope(
                $user,
                $file
            );
        }

        return false;
    }

    /**
     * Check whether the user can manage a file.
     *
     * File management is deliberately stricter than editing:
     * employees can manage only their own uploads.
     */
    public function canManageFile(
        User $user,
        File $file
    ): bool {
        if ($user->hasGlobalAccess()){
            return true;
        }

        if ($user->isAuditor()) {
            return false;
        }

        /*
         * Employee can manage only their own uploads.
         */
        if ($user->isEmployee()) {
            return
                (int) $file->uploader_id ===
                (int) $user->id;
        }

        /*
         * Department / Location Head.
         */
        if ($user->isDepartmentHead()) {
            return $this->isWithinManagerFileScope(
                $user,
                $file
            );
        }

        return false;
    }

    /**
     * Employee's own folder.
     */
    private function isOwnFolder(
        User $user,
        Folder $folder
    ): bool {
        return $folder->owner_user_id !== null
            && (int) $folder->owner_user_id ===
                (int) $user->id;
    }

    /**
     * Employee organizational folder scope.
     *
     * Allows an employee to work with folders belonging
     * to their own location and department.
     *
     * This is what allows an employee to move their own
     * file between folders such as:
     *
     * IT Documents -> Policies
     */
    private function isWithinEmployeeFolderScope(
        User $user,
        Folder $folder
    ): bool {
        /*
         * If the folder has a location, it must match
         * the employee's location.
         */
        if (
            $user->location_id !== null
            && $folder->location_id !== null
            && (int) $user->location_id ===
                (int) $folder->location_id
        ) {
            /*
             * If employee has no department restriction,
             * location scope is enough.
             */
            if ($user->department_id === null) {
                return true;
            }

            /*
             * Department must also match.
             */
            if (
                $folder->department_id !== null
                && (int) $user->department_id ===
                    (int) $folder->department_id
            ) {
                return true;
            }
        }

        /*
         * A nested folder may have no location/department
         * metadata of its own.
         *
         * Walk up through its parents and check whether
         * an ancestor belongs to the employee's scope.
         */
        $parent = $folder->parent;

        while ($parent !== null) {
            if (
                $this->isOwnFolder($user, $parent)
                || $this->isEmployeeOrganizationalFolder(
                    $user,
                    $parent
                )
            ) {
                return true;
            }

            $parent = $parent->parent;
        }

        return false;
    }

    /**
     * Check whether a folder belongs directly to the
     * employee's location/department.
     */
    private function isEmployeeOrganizationalFolder(
        User $user,
        Folder $folder
    ): bool {
        if (
            $user->location_id === null
            || $folder->location_id === null
        ) {
            return false;
        }

        if (
            (int) $user->location_id !==
            (int) $folder->location_id
        ) {
            return false;
        }

        /*
         * Employee without a department restriction.
         */
        if ($user->department_id === null) {
            return true;
        }

        /*
         * Employee must be in the same department.
         */
        return $folder->department_id !== null
            && (int) $user->department_id ===
                (int) $folder->department_id;
    }

    /**
     * Department / Location manager scope.
     */
    private function isWithinManagerScope(
        User $user,
        Folder $folder
    ): bool {
        /*
         * Same location.
         */
        if (
            $user->location_id !== null
            && $folder->location_id !== null
            && (int) $user->location_id ===
                (int) $folder->location_id
        ) {
            /*
             * Manager has no department restriction.
             */
            if ($user->department_id === null) {
                return true;
            }

            /*
             * Same department.
             */
            if (
                $folder->department_id !== null
                && (int) $user->department_id ===
                    (int) $folder->department_id
            ) {
                return true;
            }
        }

        /*
         * Manager can access subordinate users' folders.
         */
        return $this->isWithinReportingScope(
            $user,
            $folder
        );
    }

    /**
     * Manager file scope.
     */
    private function isWithinManagerFileScope(
        User $user,
        File $file
    ): bool {
        /*
         * Location / department scope.
         */
        if (
            $user->location_id !== null
            && $file->location_id !== null
            && (int) $user->location_id ===
                (int) $file->location_id
        ) {
            /*
             * Manager has no department restriction.
             */
            if ($user->department_id === null) {
                return true;
            }

            /*
             * Same department.
             */
            if (
                $file->department_id !== null
                && (int) $user->department_id ===
                    (int) $file->department_id
            ) {
                return true;
            }
        }

        /*
         * Reporting-line scope.
         */
        return $this->isFileOwnedBySubordinate(
            $user,
            $file
        );
    }

    /**
     * Check whether the file uploader is below the user
     * in the reporting hierarchy.
     */
    private function isFileOwnedBySubordinate(
        User $user,
        File $file
    ): bool {
        if ($file->uploader_id === null) {
            return false;
        }

        $owner = User::findOne([
            'id' => $file->uploader_id,
            'status' => User::STATUS_ACTIVE,
        ]);

        if ($owner === null) {
            return false;
        }

        while ($owner->manager_id !== null) {
            if (
                (int) $owner->manager_id ===
                (int) $user->id
            ) {
                return true;
            }

            $owner = User::findOne([
                'id' => $owner->manager_id,
                'status' => User::STATUS_ACTIVE,
            ]);

            if ($owner === null) {
                break;
            }
        }

        return false;
    }

    /**
     * File location / department scope.
     */
    private function isWithinFileScope(
        User $user,
        File $file
    ): bool {
        if (
            $user->location_id === null
            || $file->location_id === null
        ) {
            return false;
        }

        if (
            (int) $user->location_id !==
            (int) $file->location_id
        ) {
            return false;
        }

        /*
         * No department restriction.
         */
        if ($user->department_id === null) {
            return true;
        }

        return $file->department_id !== null
            && (int) $user->department_id ===
                (int) $file->department_id;
    }

    /**
     * Auditor assigned scope.
     */
    private function isWithinAssignedScope(
        User $user,
        Folder $folder
    ): bool {
        if (
            $user->location_id === null
            || $folder->location_id === null
        ) {
            return false;
        }

        if (
            (int) $user->location_id !==
            (int) $folder->location_id
        ) {
            return false;
        }

        if ($user->department_id === null) {
            return true;
        }

        return $folder->department_id !== null
            && (int) $user->department_id ===
                (int) $folder->department_id;
    }

    private function isWithinReportingScope(
        User $user,
        Folder $folder
    ): bool {
        if ($folder->owner_user_id === null) {
            return false;
        }

        $owner = User::findOne([
            'id' => $folder->owner_user_id,
            'status' => User::STATUS_ACTIVE,
        ]);

        if ($owner === null) {
            return false;
        }

        while ($owner->manager_id !== null) {
            if (
                (int) $owner->manager_id ===
                (int) $user->id
            ) {
                return true;
            }

            $owner = User::findOne([
                'id' => $owner->manager_id,
                'status' => User::STATUS_ACTIVE,
            ]);

            if ($owner === null) {
                break;
            }
        }

        return false;
    }

    /**
     * Explicit active file share.
     */
    private function hasActiveShare(
        User $user,
        File $file
    ): bool {
        foreach ($file->shares as $share) {
            if (
                (int) $share->shared_with_user_id ===
                    (int) $user->id
                && $share->isActive()
                && $share->canRead()
            ) {
                return true;
            }
        }

        return false;
    }
}