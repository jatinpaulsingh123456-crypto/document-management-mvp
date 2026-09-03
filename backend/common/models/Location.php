<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Location model.
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $country
 * @property int|null $parent_id
 * @property int $status
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Location|null $parent
 * @property Location[] $children
 * @property Department[] $departments
 * @property User[] $users
 * @property Folder[] $folders
 * @property File[] $files
 */
class Location extends ActiveRecord
{
    public const STATUS_INACTIVE = 0;
    public const STATUS_ACTIVE = 10;

    public static function tableName(): string
    {
        return '{{%locations}}';
    }

    public function rules(): array
    {
        return [
            [['name', 'code', 'country'], 'required'],

            ['name', 'string', 'max' => 150],

            ['code', 'string', 'max' => 50],

            ['country', 'string', 'max' => 100],

            ['parent_id', 'integer'],

            ['status', 'integer'],

            [
                'status',
                'in',
                'range' => [
                    self::STATUS_INACTIVE,
                    self::STATUS_ACTIVE,
                ],
            ],

            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * Parent location.
     *
     * Example:
     * Country → City/Branch
     */
    public function getParent(): ActiveQuery
    {
        return $this->hasOne(
            self::class,
            ['id' => 'parent_id']
        );
    }

    /**
     * Child locations.
     */
    public function getChildren(): ActiveQuery
    {
        return $this->hasMany(
            self::class,
            ['parent_id' => 'id']
        );
    }

    /**
     * Departments belonging to this location.
     */
    public function getDepartments(): ActiveQuery
    {
        return $this->hasMany(
            Department::class,
            ['location_id' => 'id']
        );
    }

    /**
     * Users assigned to this location.
     */
    public function getUsers(): ActiveQuery
    {
        return $this->hasMany(
            User::class,
            ['location_id' => 'id']
        );
    }

    /**
     * Folders belonging to this location.
     */
    public function getFolders(): ActiveQuery
    {
        return $this->hasMany(
            Folder::class,
            ['location_id' => 'id']
        );
    }

    /**
     * Files belonging to this location.
     */
    public function getFiles(): ActiveQuery
    {
        return $this->hasMany(
            File::class,
            ['location_id' => 'id']
        );
    }
}