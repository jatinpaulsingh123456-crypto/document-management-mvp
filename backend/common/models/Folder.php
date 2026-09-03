<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Folder model.
 *
 * Supports:
 *
 * Location → Department → User → Nested folders
 *
 * @property int $id
 * @property int|null $parent_id
 * @property int|null $location_id
 * @property int|null $department_id
 * @property int|null $owner_user_id
 * @property string $name
 * @property string $folder_type
 * @property int $created_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Folder|null $parent
 * @property Folder[] $children
 * @property Location|null $location
 * @property Department|null $department
 * @property User|null $ownerUser
 * @property User $createdBy
 * @property File[] $files
 * @property AuditLog[] $auditLogs
 */
class Folder extends ActiveRecord
{
    public const TYPE_LOCATION = 'location';
    public const TYPE_DEPARTMENT = 'department';
    public const TYPE_USER = 'user';
    public const TYPE_CUSTOM = 'custom';

    public static function tableName(): string
    {
        return '{{%folders}}';
    }

    public function rules(): array
    {
        return [
            /*
             * Required fields.
             */
            [
                [
                    'name',
                    'created_by',
                ],
                'required',
            ],

            /*
             * Foreign keys.
             */
            [
                [
                    'parent_id',
                    'location_id',
                    'department_id',
                    'owner_user_id',
                    'created_by',
                ],
                'integer',
            ],

            /*
             * Folder name.
             */
            [
                'name',
                'string',
                'max' => 255,
            ],

            /*
             * Folder type.
             */
            [
                'folder_type',
                'string',
                'max' => 30,
            ],

            [
                'folder_type',
                'in',
                'range' => [
                    self::TYPE_LOCATION,
                    self::TYPE_DEPARTMENT,
                    self::TYPE_USER,
                    self::TYPE_CUSTOM,
                ],
            ],

            /*
             * Timestamps.
             */
            [
                [
                    'created_at',
                    'updated_at',
                ],
                'safe',
            ],
        ];
    }

    /**
     * Parent folder.
     *
     * Allows nested folders.
     */
    public function getParent(): ActiveQuery
    {
        return $this->hasOne(
            self::class,
            ['id' => 'parent_id']
        );
    }

    /**
     * Child folders.
     */
    public function getChildren(): ActiveQuery
    {
        return $this->hasMany(
            self::class,
            ['parent_id' => 'id']
        );
    }

    /**
     * Location associated with the folder.
     */
    public function getLocation(): ActiveQuery
    {
        return $this->hasOne(
            Location::class,
            ['id' => 'location_id']
        );
    }

    /**
     * Department associated with the folder.
     */
    public function getDepartment(): ActiveQuery
    {
        return $this->hasOne(
            Department::class,
            ['id' => 'department_id']
        );
    }

    /**
     * User who owns the folder.
     */
    public function getOwnerUser(): ActiveQuery
    {
        return $this->hasOne(
            User::class,
            ['id' => 'owner_user_id']
        );
    }

    /**
     * User who created the folder.
     */
    public function getCreatedBy(): ActiveQuery
    {
        return $this->hasOne(
            User::class,
            ['id' => 'created_by']
        );
    }

    /**
     * Files contained in this folder.
     */
    public function getFiles(): ActiveQuery
    {
        return $this->hasMany(
            File::class,
            ['folder_id' => 'id']
        );
    }

    /**
     * Audit records associated with this folder.
     */
    public function getAuditLogs(): ActiveQuery
    {
        return $this->hasMany(
            AuditLog::class,
            ['folder_id' => 'id']
        );
    }

    /**
     * Returns whether this is a root folder.
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Returns whether this is a user folder.
     */
    public function isUserFolder(): bool
    {
        return $this->folder_type === self::TYPE_USER;
    }

    /**
     * Returns whether this is a location folder.
     */
    public function isLocationFolder(): bool
    {
        return $this->folder_type === self::TYPE_LOCATION;
    }

    /**
     * Returns whether this is a department folder.
     */
    public function isDepartmentFolder(): bool
    {
        return $this->folder_type === self::TYPE_DEPARTMENT;
    }

    /**
     * Returns whether this is a custom folder.
     */
    public function isCustomFolder(): bool
    {
        return $this->folder_type === self::TYPE_CUSTOM;
    }
}