<?php

declare(strict_types=1);

namespace common\services;

use common\models\AuditLog;
use common\models\File;
use common\models\Folder;
use common\models\User;
use common\storage\StorageInterface;
use RuntimeException;
use Throwable;
use Yii;
use yii\db\IntegrityException;
use yii\db\Transaction;
use yii\web\UploadedFile;

final class FileUploadService
{
    public function __construct(
        private readonly AuthorizationService $authorizationService,
        private readonly StorageInterface $storage
    ) {
    }

    /**
     * Upload a file into a folder.
     *
     * The physical file is stored first.
     * Database metadata and audit information are then persisted.
     *
     * If database persistence fails, the physical file is removed.
     */
    public function upload(
        User $user,
        Folder $folder,
        UploadedFile $uploadedFile,
        ?string $description = null,
        ?string $tags = null
    ): File {
        /*
         * User authentication is already handled by the API.
         *
         * Do NOT call getIsActive() here because the User model
         * does not expose that method.
         *
         * If your User model has a status column, check it directly.
         */
        if (
            property_exists($user, 'status')
            && defined(User::class . '::STATUS_ACTIVE')
            && $user->status !== User::STATUS_ACTIVE
        ) {
            throw new RuntimeException(
                'The user account is not active.'
            );
        }

        /*
         * Authorization check.
         */
        if (!$this->authorizationService->canUploadToFolder(
            $user,
            $folder
        )) {
            throw new RuntimeException(
                'You are not authorized to upload files to this folder.'
            );
        }

        /*
         * Validate uploaded file.
         */
        if ($uploadedFile->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(
                'File upload failed. Upload error code: '
                . $uploadedFile->error
            );
        }

        if ($uploadedFile->tempName === '') {
            throw new RuntimeException(
                'Uploaded file has no temporary path.'
            );
        }

        if (!is_file($uploadedFile->tempName)) {
            throw new RuntimeException(
                'Uploaded temporary file does not exist.'
            );
        }

        if (
            $uploadedFile->size === null
            || $uploadedFile->size < 0
        ) {
            throw new RuntimeException(
                'Unable to determine uploaded file size.'
            );
        }

        /*
         * Generate a safe extension.
         */
        $extension = $uploadedFile->extension !== ''
            ? strtolower(
                preg_replace(
                    '/[^a-zA-Z0-9]/',
                    '',
                    $uploadedFile->extension
                ) ?? ''
            )
            : '';

        /*
         * Generate a random filename.
         *
         * Never use the client filename as the physical storage filename.
         */
        $uniqueId = bin2hex(random_bytes(16));

        $datePath = date('Y/m/d');

        $filename = $uniqueId;

        if ($extension !== '') {
            $filename .= '.' . $extension;
        }

        /*
         * IMPORTANT:
         *
         * LocalStorage already has a base directory:
         *
         * common/storage/files
         *
         * Therefore the storage key must NOT unnecessarily
         * duplicate that directory.
         */
        $storageKey = sprintf(
            '%s/%s',
            $datePath,
            $filename
        );

        /*
         * Calculate SHA-256 checksum before moving the file.
         */
        $checksum = hash_file(
            'sha256',
            $uploadedFile->tempName
        );

        if ($checksum === false) {
            throw new RuntimeException(
                'Unable to calculate file checksum.'
            );
        }

        /*
         * GLOBAL duplicate-content protection.
         *
         * The checksum is calculated from the actual uploaded
         * file. Folder, filename, uploader and location do not
         * affect duplicate detection.
         */
        $duplicateExists = File::find()
            ->where([
                'checksum' => $checksum,
                'status' => File::STATUS_ACTIVE,
            ])
            ->exists();

        if ($duplicateExists) {
            throw new RuntimeException(
                'This document already exists in the system.'
            );
        }

        /*
         * Create File model.
         */
        $file = new File();

        $file->folder_id = $folder->id;
        $file->uploader_id = $user->id;

        /*
         * Use the folder's organizational scope.
         */
        $file->location_id = $folder->location_id;
        $file->department_id = $folder->department_id;

        /*
         * Internal generated filename.
         */
        $file->name = $filename;

        /*
         * Original client filename.
         */
        $file->original_name = $uploadedFile->name;

        /*
         * Physical storage key.
         */
        $file->storage_key = $storageKey;

        /*
         * MIME type.
         */
        $file->mime_type = $uploadedFile->type !== ''
            ? $uploadedFile->type
            : 'application/octet-stream';

        /*
         * File extension.
         */
        $file->extension = $extension !== ''
            ? $extension
            : null;

        /*
         * Size.
         */
        $file->size_bytes = (int) $uploadedFile->size;

        /*
         * Optional metadata.
         */
        $file->description = $description;
        $file->tags = $tags;

        /*
         * Integrity checksum.
         */
        $file->checksum = $checksum;

        /*
         * Active status.
         */
        $file->status = File::STATUS_ACTIVE;

        /*
         * Timestamps.
         */
        $now = date('Y-m-d H:i:s');

        $file->uploaded_at = $now;
        $file->updated_at = $now;

        $stored = false;

        try {
            /*
             * ---------------------------------------------------------
             * 1. Store physical file
             * ---------------------------------------------------------
             */
            $this->storage->put(
                $uploadedFile,
                $storageKey
            );

            $stored = true;

            /*
             * ---------------------------------------------------------
             * 2. Start DB transaction
             * ---------------------------------------------------------
             */
            $transaction = Yii::$app->db->beginTransaction(
                Transaction::SERIALIZABLE
            );

            try {
                /*
                 * Save file metadata.
                 */
                try {
                    if (!$file->save(false)) {
                        throw new RuntimeException(
                            'Unable to save file metadata.'
                        );
                    }
                } catch (IntegrityException $e) {
                    throw new RuntimeException(
                        'This document already exists in the system.',
                        0,
                        $e
                    );
                }

                /*
                 * -----------------------------------------------------
                 * Audit log
                 * -----------------------------------------------------
                 */
                $audit = new AuditLog();

                $audit->user_id = $user->id;
                $audit->action = AuditLog::ACTION_UPLOAD;
                $audit->entity_type = AuditLog::ENTITY_FILE;
                $audit->entity_id = $file->id;
                $audit->file_id = $file->id;
                $audit->folder_id = $folder->id;

                /*
                 * Request information.
                 */
                $audit->ip_address = Yii::$app->request->userIP;

                $userAgent = Yii::$app->request->userAgent;

                $audit->user_agent = $userAgent !== null
                    ? substr($userAgent, 0, 1000)
                    : null;

                /*
                 * Audit metadata.
                 */
                $audit->setMetadataArray([
                    'original_name' => $file->original_name,
                    'size_bytes' => $file->size_bytes,
                    'mime_type' => $file->mime_type,
                    'checksum' => $file->checksum,
                    'storage_key' => $file->storage_key,
                ]);

                $audit->created_at = $now;

                /*
                 * Save audit record.
                 */
                if (!$audit->save(false)) {
                    throw new RuntimeException(
                        'Unable to save audit log.'
                    );
                }

                /*
                 * Commit both DB records.
                 */
                $transaction->commit();
            } catch (Throwable $e) {
                /*
                 * Roll back database changes.
                 */
                if ($transaction->getIsActive()) {
                    $transaction->rollBack();
                }

                throw $e;
            }
        } catch (Throwable $e) {
            /*
             * ---------------------------------------------------------
             * Cleanup
             * ---------------------------------------------------------
             *
             * If the physical file was successfully stored but
             * database persistence failed, delete the physical file
             * so we do not leave an orphaned upload.
             */
            if ($stored) {
                try {
                    $this->storage->delete($storageKey);
                } catch (Throwable $cleanupException) {
                    Yii::error(
                        [
                            'message' => 'Failed to clean up uploaded file.',
                            'storage_key' => $storageKey,
                            'exception' => $cleanupException,
                        ],
                        __METHOD__
                    );
                }
            }

            throw $e;
        }

        return $file;
    }
}