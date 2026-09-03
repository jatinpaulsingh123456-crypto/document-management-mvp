<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Role model.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User[] $users
 */
class Role extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%roles}}';
    }

    public function rules(): array
    {
        return [
            [['code', 'name'], 'required'],

            ['code', 'string', 'max' => 50],

            ['name', 'string', 'max' => 100],

            ['description', 'string'],

            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function getUsers(): ActiveQuery
    {
        return $this->hasMany(
            User::class,
            ['role_id' => 'id']
        );
    }
}