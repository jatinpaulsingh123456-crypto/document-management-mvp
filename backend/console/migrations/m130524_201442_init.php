<?php

declare(strict_types=1);

use yii\db\Migration;

class m130524_201442_init extends Migration
{
    public function safeUp(): void
    {
        $tableOptions = null;

        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%users}}', [
            // Primary key
            'id' => $this->primaryKey(),

            // Authentication
            'username' => $this->string(100)->notNull()->unique(),
            'email' => $this->string(255)->notNull()->unique(),
            'auth_key' => $this->string(64)->notNull(),
            'password_hash' => $this->string(255)->notNull(),
            'password_reset_token' => $this->string(255)->null()->unique(),
            'verification_token' => $this->string(255)->null(),

            // Organization
            'role_id' => $this->integer()->null(),
            'location_id' => $this->integer()->null(),
            'department_id' => $this->integer()->null(),
            'manager_id' => $this->integer()->null(),

            // User information
            'name' => $this->string(150)->notNull(),

            // Account status
            'status' => $this->smallInteger()
                ->notNull()
                ->defaultValue(10),

            // Login tracking
            'last_login_at' => $this->dateTime()->null(),

            // Timestamps
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        /*
         * Indexes
         */

        $this->createIndex(
            'idx-users-role_id',
            '{{%users}}',
            'role_id'
        );

        $this->createIndex(
            'idx-users-location_id',
            '{{%users}}',
            'location_id'
        );

        $this->createIndex(
            'idx-users-department_id',
            '{{%users}}',
            'department_id'
        );

        $this->createIndex(
            'idx-users-manager_id',
            '{{%users}}',
            'manager_id'
        );

        $this->createIndex(
            'idx-users-status',
            '{{%users}}',
            'status'
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%users}}');
    }
}