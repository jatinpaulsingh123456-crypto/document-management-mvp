<?php

declare(strict_types=1);

namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property int $id
 *
 * @property string $username
 * @property string $email
 * @property string $name
 *
 * @property string $password_hash
 * @property string|null $password_reset_token
 * @property string|null $verification_token
 * @property string $auth_key
 *
 * @property int $status
 *
 * @property int|null $role_id
 * @property int|null $location_id
 * @property int|null $department_id
 * @property int|null $manager_id
 *
 * @property string|null $last_login_at
 * @property int $created_at
 * @property int $updated_at
 *
 * @property string $password write-only password
 *
 * @property Role|null $role
 * @property Location|null $location
 * @property Department|null $department
 * @property User|null $manager
 * @property User[] $subordinates
 * @property Folder[] $ownedFolders
 * @property File[] $uploadedFiles
 */
class User extends ActiveRecord implements IdentityInterface
{
    public const STATUS_DELETED = 0;
    public const STATUS_INACTIVE = 9;
    public const STATUS_ACTIVE = 10;

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%users}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            /*
             * Basic user information.
             */
            [
                [
                    'username',
                    'email',
                    'name',
                ],
                'string',
            ],

            [
                [
                    'username',
                    'email',
                    'name',
                ],
                'required',
            ],

            [
                'username',
                'string',
                'max' => 100,
            ],

            [
                'email',
                'string',
                'max' => 255,
            ],

            [
                'name',
                'string',
                'max' => 150,
            ],

            /*
             * Authentication fields.
             */
            [
                'password_hash',
                'string',
                'max' => 255,
            ],

            [
                'auth_key',
                'string',
                'max' => 64,
            ],

            [
                'password_reset_token',
                'string',
                'max' => 255,
            ],

            [
                'verification_token',
                'string',
                'max' => 255,
            ],

            /*
             * Organization fields.
             *
             * role_id is nullable in the database during the
             * initial migration, but a normal application user
             * must have a role.
             */
            [
                [
                    'role_id',
                    'location_id',
                    'department_id',
                    'manager_id',
                ],
                'integer',
            ],

            [
                'role_id',
                'required',
            ],

            /*
             * Account status.
             */
            [
                'status',
                'default',
                'value' => self::STATUS_INACTIVE,
            ],

            [
                'status',
                'in',
                'range' => [
                    self::STATUS_ACTIVE,
                    self::STATUS_INACTIVE,
                    self::STATUS_DELETED,
                ],
            ],

            /*
             * Optional fields.
             */
            [
                [
                    'location_id',
                    'department_id',
                    'manager_id',
                ],
                'default',
                'value' => null,
            ],

            [
                'last_login_at',
                'safe',
            ],
        ];
    }

    /**
     * Finds an active user by ID.
     *
     * {@inheritdoc}
     */
    public static function findIdentity($id): ?self
    {
        return static::findOne([
            'id' => $id,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds a user by access token.
     *
     * JWT authentication will be implemented later.
     *
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken(
    $token,
    $type = null
    ): ?self {
    return static::findOne([
        'auth_key' => $token,
        'status' => self::STATUS_ACTIVE,
      ]);
   }

    /**
     * Finds an active user by username.
     */
    public static function findByUsername(string $username): ?self
    {
        return static::findOne([
            'username' => $username,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds an active user by email.
     */
    public static function findByEmail(string $email): ?self
    {
        return static::findOne([
            'email' => $email,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds an active user by password reset token.
     */
    public static function findByPasswordResetToken(
        string $token
    ): ?self {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds an inactive user by verification token.
     */
    public static function findByVerificationToken(
        string $token
    ): ?self {
        return static::findOne([
            'verification_token' => $token,
            'status' => self::STATUS_INACTIVE,
        ]);
    }

    /**
     * Checks whether a password reset token is still valid.
     */
    public static function isPasswordResetTokenValid(
        ?string $token
    ): bool {
        if ($token === null || $token === '') {
            return false;
        }

        $separatorPosition = strrpos($token, '_');

        if ($separatorPosition === false) {
            return false;
        }

        $timestamp = (int) substr(
            $token,
            $separatorPosition + 1
        );

        $expire = (int) Yii::$app->params['user.passwordResetTokenExpire'];

        return $timestamp + $expire >= time();
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): int
    {
        return (int) $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey(): string
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey): bool
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates a password against the stored password hash.
     */
    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword(
            $password,
            $this->password_hash
        );
    }

    /**
     * Sets a password and generates its secure hash.
     */
    public function setPassword(string $password): void
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash(
            $password
        );
    }

    /**
     * Generates authentication key.
     */
    public function generateAuthKey(): void
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates password reset token.
     */
    public function generatePasswordResetToken(): void
    {
        $this->password_reset_token =
            Yii::$app->security->generateRandomString()
            . '_'
            . time();
    }

    /**
     * Generates email verification token.
     */
    public function generateEmailVerificationToken(): void
    {
        $this->verification_token =
            Yii::$app->security->generateRandomString()
            . '_'
            . time();
    }

    /**
     * Removes password reset token.
     */
    public function removePasswordResetToken(): void
    {
        $this->password_reset_token = null;
    }

    /*
     * -------------------------------------------------------------------------
     * Organization relationships
     * -------------------------------------------------------------------------
     */

    /**
     * Role assigned to this user.
     */
    public function getRole(): ActiveQuery
    {
        return $this->hasOne(
            Role::class,
            ['id' => 'role_id']
        );
    }

    /**
     * Location assigned to this user.
     */
    public function getLocation(): ActiveQuery
    {
        return $this->hasOne(
            Location::class,
            ['id' => 'location_id']
        );
    }

    /**
     * Department assigned to this user.
     */
    public function getDepartment(): ActiveQuery
    {
        return $this->hasOne(
            Department::class,
            ['id' => 'department_id']
        );
    }

    /**
     * User's manager.
     */
    public function getManager(): ActiveQuery
    {
        return $this->hasOne(
            self::class,
            ['id' => 'manager_id']
        );
    }

    /**
     * Users reporting to this user.
     */
    public function getSubordinates(): ActiveQuery
    {
        return $this->hasMany(
            self::class,
            ['manager_id' => 'id']
        );
    }

    /*
     * -------------------------------------------------------------------------
     * File management relationships
     * -------------------------------------------------------------------------
     */

    /**
     * Folders owned by this user.
     */
    public function getOwnedFolders(): ActiveQuery
    {
        return $this->hasMany(
            Folder::class,
            ['owner_user_id' => 'id']
        );
    }

    /**
     * Files uploaded by this user.
     */
    public function getUploadedFiles(): ActiveQuery
    {
        return $this->hasMany(
            File::class,
            ['uploader_id' => 'id']
        );
    }

    /*
     * -------------------------------------------------------------------------
     * Role helpers
     * -------------------------------------------------------------------------
     */

    /**
     * Returns true when the user has the given role.
     */
    public function hasRole(string $roleCode): bool
    {
        return $this->role !== null
            && $this->role->code === $roleCode;
    }

    /**
     * Returns true for CEO, MD, and Country Head.
     *
     * These roles have organization-wide management access
     * according to the MVP access matrix.
     */
    public function hasGlobalAccess(): bool
    {
        return $this->hasRole('CEO')
            || $this->hasRole('MD')
            || $this->hasRole('COUNTRY_HEAD');
    }

    /**
     * Returns true for CEO.
     */
    public function isCeo(): bool
    {
        return $this->hasRole('CEO');
    }

    /**
     * Returns true for Managing Director.
     */
    public function isManagingDirector(): bool
    {
        return $this->hasRole('MD');
    }

    /**
     * Returns true for Country Head.
     */
    public function isCountryHead(): bool
    {
        return $this->hasRole('COUNTRY_HEAD');
    }

    /**
     * Returns true for Department Head.
     */
    public function isDepartmentHead(): bool
    {
        return $this->hasRole('DEPARTMENT_HEAD');
    }

    /**
     * Returns true for Employee.
     */
    public function isEmployee(): bool
    {
        return $this->hasRole('EMPLOYEE');
    }

    /**
     * Returns true for Auditor.
     */
    public function isAuditor(): bool
    {
        return $this->hasRole('AUDITOR');
    }
}
