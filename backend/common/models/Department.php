<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Department model.
 *
 * @property int $id
 * @property int $location_id
 * @property string $name
 * @property string $code
 * @property int|null $parent_id
 * @property int $status
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Location $location
 * @property Department|null $parent
 * @property Department[] $children
 * @property User[] $users
 * @property Folder[] $folders
 * @property File[] $files
 */
class Department extends ActiveRecord
{
    public const STATUS_INACTIVE = 0;
    public const STATUS_ACTIVE = 10;

    public static function tableName(): string
    {
        return '{{%departments}}';
    }

    public function rules(): array
    {
        return [
            [
                [
                    'location_id',
                    'name',
                    'code',
                ],
                'required',
            ],

            [
                [
                    'location_id',
                    'parent_id',
                    'status',
                ],
                'integer',
            ],

            [
                'name',
                'string',
                'max' => 150,
            ],

            [
                'code',
                'string',
                'max' => 50,
            ],

            [
                'status',
                'in',
                'range' => [
                    self::STATUS_INACTIVE,
                    self::STATUS_ACTIVE,
                ],
            ],

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
     * Location this department belongs to.
     */
    public function getLocation(): ActiveQuery
    {
        return $this->hasOne(
            Location::class,
            ['id' => 'location_id']
        );
    }

    /**
     * Parent department.
     */
    public function getParent(): ActiveQuery
    {
        return $this->hasOne(
            self::class,
            ['id' => 'parent_id']
        );
    }

    /**
     * Child departments.
     */
    public function getChildren(): ActiveQuery
    {
        return $this->hasMany(
            self::class,
            ['parent_id' => 'id']
        );
    }

    /**
     * Users belonging to this department.
     */
    public function getUsers(): ActiveQuery
    {
        return $this->hasMany(
            User::class,
            ['department_id' => 'id']
        );
    }

    /**
     * Folders belonging to this department.
     */
    public function getFolders(): ActiveQuery
    {
        return $this->hasMany(
            Folder::class,
            ['department_id' => 'id']
        );
    }

    /**
     * Files belonging to this department.₹ 
     */
    public function getFiles(): ActiveQuery
    {
        return $this->hasMany(
            File::class,
            ['department_id' => 'id']
        );
    }
}