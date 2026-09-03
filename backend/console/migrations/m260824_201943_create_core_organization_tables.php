<?php

declare(strict_types=1);

use yii\db\Migration;

class m260824_201943_create_core_organization_tables extends Migration
{
    public function safeUp(): void
    {
        /*
         * ============================================================
         * ROLES
         * ============================================================
         */
        $this->createTable('{{%roles}}', [
            'id' => $this->primaryKey(),

            'code' => $this->string(50)
                ->notNull()
                ->unique(),

            'name' => $this->string(100)
                ->notNull(),

            'description' => $this->text()
                ->null(),

            'created_at' => $this->dateTime()
                ->notNull(),

            'updated_at' => $this->dateTime()
                ->notNull(),
        ]);

        /*
         * ============================================================
         * LOCATIONS
         * ============================================================
         */
        $this->createTable('{{%locations}}', [
            'id' => $this->primaryKey(),

            'name' => $this->string(150)
                ->notNull(),

            'code' => $this->string(50)
                ->notNull()
                ->unique(),

            'country' => $this->string(100)
                ->notNull(),

            'parent_id' => $this->integer()
                ->null(),

            'status' => $this->smallInteger()
                ->notNull()
                ->defaultValue(10),

            'created_at' => $this->dateTime()
                ->notNull(),

            'updated_at' => $this->dateTime()
                ->notNull(),
        ]);

        $this->createIndex(
            'idx-locations-parent_id',
            '{{%locations}}',
            'parent_id'
        );

        $this->addForeignKey(
            'fk-locations-parent_id',
            '{{%locations}}',
            'parent_id',
            '{{%locations}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        /*
         * ============================================================
         * DEPARTMENTS
         * ============================================================
         */
        $this->createTable('{{%departments}}', [
            'id' => $this->primaryKey(),

            'location_id' => $this->integer()
                ->notNull(),

            'name' => $this->string(150)
                ->notNull(),

            'code' => $this->string(50)
                ->notNull(),

            'parent_id' => $this->integer()
                ->null(),

            'status' => $this->smallInteger()
                ->notNull()
                ->defaultValue(10),

            'created_at' => $this->dateTime()
                ->notNull(),

            'updated_at' => $this->dateTime()
                ->notNull(),
        ]);

        $this->createIndex(
            'idx-departments-location_id',
            '{{%departments}}',
            'location_id'
        );

        $this->createIndex(
            'idx-departments-parent_id',
            '{{%departments}}',
            'parent_id'
        );

        $this->createIndex(
            'uq-departments-location-code',
            '{{%departments}}',
            [
                'location_id',
                'code',
            ],
            true
        );

        $this->addForeignKey(
            'fk-departments-location_id',
            '{{%departments}}',
            'location_id',
            '{{%locations}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-departments-parent_id',
            '{{%departments}}',
            'parent_id',
            '{{%departments}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        /*
         * ============================================================
         * EXISTING USERS TABLE
         * ============================================================
         *
         * IMPORTANT:
         * users is created by:
         *
         * m130524_201442_init
         *
         * Therefore DO NOT create users here.
         *
         * This migration only adds the organization relationships
         * to the existing users table.
         */

        $this->addForeignKey(
            'fk-users-role_id',
            '{{%users}}',
            'role_id',
            '{{%roles}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-users-location_id',
            '{{%users}}',
            'location_id',
            '{{%locations}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-users-department_id',
            '{{%users}}',
            'department_id',
            '{{%departments}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-users-manager_id',
            '{{%users}}',
            'manager_id',
            '{{%users}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        /*
         * ============================================================
         * DEFAULT ROLES
         * ============================================================
         */
        $now = date('Y-m-d H:i:s');

        $this->batchInsert(
            '{{%roles}}',
            [
                'code',
                'name',
                'description',
                'created_at',
                'updated_at',
            ],
            [
                [
                    'CEO',
                    'Chief Executive Officer',
                    'Full access to all locations, departments, users, folders and files.',
                    $now,
                    $now,
                ],
                [
                    'MD',
                    'Managing Director',
                    'Full access to all locations, departments, users, folders and files.',
                    $now,
                    $now,
                ],
                [
                    'COUNTRY_HEAD',
                    'Country Head',
                    'Full access within assigned country/location scope.',
                    $now,
                    $now,
                ],
                [
                    'DEPARTMENT_HEAD',
                    'Department Head',
                    'Management access within assigned department and subordinate scope.',
                    $now,
                    $now,
                ],
                [
                    'EMPLOYEE',
                    'Employee',
                    'Access to own folder and explicitly shared resources.',
                    $now,
                    $now,
                ],
                [
                    'AUDITOR',
                    'Auditor',
                    'Read-only access within assigned scope.',
                    $now,
                    $now,
                ],
            ]
        );
    }

    public function safeDown(): void
    {
        /*
         * Remove users foreign keys first.
         */
        $this->dropForeignKey(
            'fk-users-manager_id',
            '{{%users}}'
        );

        $this->dropForeignKey(
            'fk-users-department_id',
            '{{%users}}'
        );

        $this->dropForeignKey(
            'fk-users-location_id',
            '{{%users}}'
        );

        $this->dropForeignKey(
            'fk-users-role_id',
            '{{%users}}'
        );

        /*
         * Remove department foreign keys.
         */
        $this->dropForeignKey(
            'fk-departments-parent_id',
            '{{%departments}}'
        );

        $this->dropForeignKey(
            'fk-departments-location_id',
            '{{%departments}}'
        );

        /*
         * Remove location self-reference.
         */
        $this->dropForeignKey(
            'fk-locations-parent_id',
            '{{%locations}}'
        );

        /*
         * IMPORTANT:
         * Do NOT drop users here.
         * users belongs to m130524_201442_init.
         */

        $this->dropTable('{{%departments}}');
        $this->dropTable('{{%locations}}');
        $this->dropTable('{{%roles}}');
    }
}