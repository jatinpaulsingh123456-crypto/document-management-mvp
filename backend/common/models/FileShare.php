<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * FileShare model.
 *
 * Represents an explicit file share from one user to another user.
 *
 * @property int $id
 * @property int $file_id
 * @property int $shared_with_user_id
 * @property int $shared_by_user_id
 * @property string $permission
 * @property string|null $expires_at
 * @property string $created_at
 *
 * @property File $file
 * @property User $sharedWithUser
 * @property User $sharedByUser
 */
class FileShare extends ActiveRecord
{
    public const PERMISSION_READ = 'read';
    public const PERMISSION_WRITE = 'write';
    public const PERMISSION_MANAGE = 'manage';

    public static function tableName(): string
    {
        return '{{%file_shares}}';
    }

    public function rules(): array
    {
        return [
            [
                [
                    'file_id',
                    'shared_with_user_id',
                    'shared_by_user_id',
                ],
                'required',
            ],

            [
                [
                    'file_id',
                    'shared_with_user_id',
                    'shared_by_user_id',
                ],
                'integer',
            ],

            [
                'permission',
                'string',
                'max' => 30,
            ],

            [
                'permission',
                'in',
                'range' => [
                    self::PERMISSION_READ,
                    self::PERMISSION_WRITE,
                    self::PERMISSION_MANAGE,
                ],
            ],

            [
                'expires_at',
                'safe',
            ],

            [
                'created_at',
                'safe',
            ],
        ];
    }

    /**
     * File being shared.
     */
    public function getFile(): ActiveQuery
    {
        return $this->hasOne(
            File::class,
            ['id' => 'file_id']
        );
    }

    /**
     * User receiving the share.
     */
    public function getSharedWithUser(): ActiveQuery
    {
        return $this->hasOne(
            User::class,
            ['id' => 'shared_with_user_id']
        );
    }

    /**
     * User who created the share.
     */
    public function getSharedByUser(): ActiveQuery
    {
        return $this->hasOne(
            User::class,
            ['id' => 'shared_by_user_id']
        );
    }

    /**
     * Returns whether the share has an expiry date.
     */
    public function isExpiring(): bool
    {
        return $this->expires_at !== null;
    }

    /**
     * Returns whether the share has expired.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return strtotime($this->expires_at) < time();
    }

    /**
     * Returns whether the share is currently usable.
     */
    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Returns whether the permission allows reading.
     */
    public function canRead(): bool
    {
        return in_array(
            $this->permission,
            [
                self::PERMISSION_READ,
                self::PERMISSION_WRITE,
                self::PERMISSION_MANAGE,
            ],
            true
        );
    }

    /**
     * Returns whether the permission allows writing.
     */
    public function canWrite(): bool
    {
        return in_array(
            $this->permission,
            [
                self::PERMISSION_WRITE,
                self::PERMISSION_MANAGE,
            ],
            true
        );
    }

    /**
     * Returns whether the permission allows management.
     */
    public function canManage(): bool
    {
        return $this->permission === self::PERMISSION_MANAGE;
    }
}