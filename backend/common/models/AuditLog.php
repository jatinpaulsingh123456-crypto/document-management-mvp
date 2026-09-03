<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * AuditLog model.
 *
 * Records important actions performed by users.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string $entity_type
 * @property int|null $entity_id
 * @property int|null $file_id
 * @property int|null $folder_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $metadata
 * @property string $created_at
 *
 * @property User|null $user
 * @property File|null $file
 * @property Folder|null $folder
 */
class AuditLog extends ActiveRecord
{
    /*
     * Common actions.
     */
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';

    public const ACTION_UPLOAD = 'upload';
    public const ACTION_DOWNLOAD = 'download';

    public const ACTION_CREATE = 'create';
    public const ACTION_RENAME = 'rename';
    public const ACTION_MOVE = 'move';
    public const ACTION_DELETE = 'delete';

    public const ACTION_SHARE = 'share';
    public const ACTION_UNSHARE = 'unshare';

    public const ACTION_VIEW = 'view';

    /*
     * Entity types.
     */
    public const ENTITY_USER = 'user';
    public const ENTITY_FOLDER = 'folder';
    public const ENTITY_FILE = 'file';
    public const ENTITY_SHARE = 'file_share';

    public static function tableName(): string
    {
        return '{{%audit_logs}}';
    }

    public function rules(): array
    {
        return [
            /*
             * User is optional because audit records can survive
             * user deletion.
             */
            [
                [
                    'user_id',
                    'entity_id',
                    'file_id',
                    'folder_id',
                ],
                'integer',
            ],

            /*
             * Action.
             */
            [
                'action',
                'required',
            ],

            [
                'action',
                'string',
                'max' => 50,
            ],

            /*
             * Entity type.
             */
            [
                'entity_type',
                'required',
            ],

            [
                'entity_type',
                'string',
                'max' => 50,
            ],

            /*
             * IP address.
             *
             * IPv4 and IPv6 are both supported.
             */
            [
                'ip_address',
                'string',
                'max' => 45,
            ],

            /*
             * Browser/client information.
             */
            [
                'user_agent',
                'string',
                'max' => 1000,
            ],

            /*
             * Additional structured information.
             *
             * Stored as TEXT in the database. The service layer
             * can JSON-encode arrays before saving.
             */
            [
                'metadata',
                'string',
            ],

            [
                'created_at',
                'safe',
            ],
        ];
    }

    /**
     * User who performed the action.
     */
    public function getUser(): ActiveQuery
    {
        return $this->hasOne(
            User::class,
            ['id' => 'user_id']
        );
    }

    /**
     * File associated with the audit record.
     */
    public function getFile(): ActiveQuery
    {
        return $this->hasOne(
            File::class,
            ['id' => 'file_id']
        );
    }

    /**
     * Folder associated with the audit record.
     */
    public function getFolder(): ActiveQuery
    {
        return $this->hasOne(
            Folder::class,
            ['id' => 'folder_id']
        );
    }

    /**
     * Returns decoded metadata.
     *
     * Returns null when metadata is empty or invalid JSON.
     */
    public function getMetadataArray(): ?array
    {
        if ($this->metadata === null || $this->metadata === '') {
            return null;
        }

        $decoded = json_decode(
            $this->metadata,
            true
        );

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Sets metadata from an array.
     */
    public function setMetadataArray(array $metadata): void
    {
        $this->metadata = json_encode(
            $metadata,
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * Returns whether this audit record belongs to a file.
     */
    public function isFileAction(): bool
    {
        return $this->entity_type === self::ENTITY_FILE;
    }

    /**
     * Returns whether this audit record belongs to a folder.
     */
    public function isFolderAction(): bool
    {
        return $this->entity_type === self::ENTITY_FOLDER;
    }

    /**
     * Returns whether this audit record represents a share action.
     */
    public function isShareAction(): bool
    {
        return $this->entity_type === self::ENTITY_SHARE;
    }
}