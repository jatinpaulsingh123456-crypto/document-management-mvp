<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * File model.
 *
 * Stores file metadata. The physical file is stored by the
 * storage service (local disk / MinIO), referenced by storage_key.
 *
 * @property int $id
 * @property int $folder_id
 * @property int $uploader_id
 * @property int|null $location_id
 * @property int|null $department_id
 * @property string $name
 * @property string $original_name
 * @property string $storage_key
 * @property string $mime_type
 * @property string|null $extension
 * @property int $size_bytes
 * @property string|null $description
 * @property string|null $tags
 * @property string|null $checksum
 * @property string $status
 * @property string $uploaded_at
 * @property string $updated_at
 *
 * @property Folder $folder
 * @property User $uploader
 * @property Location|null $location
 * @property Department|null $department
 * @property FileShare[] $shares
 * @property AuditLog[] $auditLogs
 */
class File extends ActiveRecord
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_ARCHIVED = 'archived';

    public static function tableName(): string
    {
        return '{{%files}}';
    }

    public function rules(): array
    {
        return [
            /*
             * Required metadata.
             */
            [
                [
                    'folder_id',
                    'uploader_id',
                    'name',
                    'original_name',
                    'storage_key',
                    'mime_type',
                    'size_bytes',
                ],
                'required',
            ],

            /*
             * Foreign keys / numeric fields.
             */
            [
                [
                    'folder_id',
                    'uploader_id',
                    'location_id',
                    'department_id',
                    'size_bytes',
                ],
                'integer',
            ],

            /*
             * Names.
             */
            [
                [
                    'name',
                    'original_name',
                ],
                'string',
                'max' => 255,
            ],

            /*
             * Storage identifier.
             *
             * Deliberately 512 characters to match the migration.
             */
            [
                'storage_key',
                'string',
                'max' => 512,
            ],

            /*
             * MIME type.
             */
            [
                'mime_type',
                'string',
                'max' => 150,
            ],

            /*
             * Extension.
             */
            [
                'extension',
                'string',
                'max' => 20,
            ],

            /*
             * Optional metadata.
             */
            [
                [
                    'description',
                    'tags',
                ],
                'string',
            ],

            /*
             * Checksum.
             */
            [
                'checksum',
                'string',
                'max' => 128,
            ],

            /*
             * File status.
             */
            [
                'status',
                'string',
                'max' => 30,
            ],

            [
                'status',
                'in',
                'range' => [
                    self::STATUS_ACTIVE,
                    self::STATUS_DELETED,
                    self::STATUS_ARCHIVED,
                ],
            ],

            /*
             * Timestamps.
             */
            [
                [
                    'uploaded_at',
                    'updated_at',
                ],
                'safe',
            ],
        ];
    }

    /**
     * Folder containing the file.
     */
    public function getFolder(): ActiveQuery
    {
        return $this->hasOne(
            Folder::class,
            ['id' => 'folder_id']
        );
    }

    /**
     * User who uploaded the file.
     */
    public function getUploader(): ActiveQuery
    {
        return $this->hasOne(
            User::class,
            ['id' => 'uploader_id']
        );
    }

    /**
     * Location associated with the file.
     */
    public function getLocation(): ActiveQuery
    {
        return $this->hasOne(
            Location::class,
            ['id' => 'location_id']
        );
    }

    /**
     * Department associated with the file.
     */
    public function getDepartment(): ActiveQuery
    {
        return $this->hasOne(
            Department::class,
            ['id' => 'department_id']
        );
    }

    /**
     * Users this file has been explicitly shared with.
     */
    public function getShares(): ActiveQuery
    {
        return $this->hasMany(
            FileShare::class,
            ['file_id' => 'id']
        );
    }

    /**
     * Audit records associated with this file.
     */
    public function getAuditLogs(): ActiveQuery
    {
        return $this->hasMany(
            AuditLog::class,
            ['file_id' => 'id']
        );
    }

    /**
     * Returns whether the file is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Returns whether the file is deleted.
     */
    public function isDeleted(): bool
    {
        return $this->status === self::STATUS_DELETED;
    }

    /**
     * Returns whether the file is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Returns the file extension in lowercase.
     */
    public function getNormalizedExtension(): ?string
    {
        if ($this->extension === null || $this->extension === '') {
            return null;
        }

        return strtolower(ltrim($this->extension, '.'));
    }

    /**
     * Returns whether the file is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with(
            strtolower($this->mime_type),
            'image/'
        );
    }

    /**
     * Returns whether the file is a video.
     */
    public function isVideo(): bool
    {
        return str_starts_with(
            strtolower($this->mime_type),
            'video/'
        );
    }

    /**
     * Returns whether the file is a PDF.
     */
    public function isPdf(): bool
    {
        return strtolower($this->mime_type) === 'application/pdf';
    }
}