<?php

use yii\db\Migration;

class m260824_205303_create_file_shares_and_audit_logs extends Migration
{
    /**
     * {@inheritdoc}
     */
   public function safeUp()
    {
        /*
         * File shares
         *
         * Allows a file to be explicitly shared with another user.
         */
        $this->createTable('{{%file_shares}}', [
            'id' => $this->bigPrimaryKey(),

            'file_id' => $this->bigInteger()->notNull(),

            'shared_with_user_id' => $this->integer()->notNull(),

            'shared_by_user_id' => $this->integer()->notNull(),

            'permission' => $this->string(30)
                ->notNull()
                ->defaultValue('read'),

            'expires_at' => $this->dateTime()->null(),

            'created_at' => $this->dateTime()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-file-shares-file_id',
            '{{%file_shares}}',
            'file_id',
            '{{%files}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-file-shares-shared-with-user',
            '{{%file_shares}}',
            'shared_with_user_id',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-file-shares-shared-by-user',
            '{{%file_shares}}',
            'shared_by_user_id',
            '{{%users}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->createIndex(
            'idx-file-shares-file_id',
            '{{%file_shares}}',
            'file_id'
        );

        $this->createIndex(
            'idx-file-shares-shared-with-user',
            '{{%file_shares}}',
            'shared_with_user_id'
        );

        $this->createIndex(
            'uq-file-share-user',
            '{{%file_shares}}',
            [
                'file_id',
                'shared_with_user_id',
            ],
            true
        );

        /*
         * Audit logs
         *
         * Records important actions performed by users.
         */
        $this->createTable('{{%audit_logs}}', [
            'id' => $this->bigPrimaryKey(),

            'user_id' => $this->integer()->null(),

            'action' => $this->string(50)->notNull(),

            'entity_type' => $this->string(50)->notNull(),

            'entity_id' => $this->bigInteger()->null(),

            'file_id' => $this->bigInteger()->null(),

            'folder_id' => $this->bigInteger()->null(),

            'ip_address' => $this->string(45)->null(),

            'user_agent' => $this->string(1000)->null(),

            'metadata' => $this->text()->null(),

            'created_at' => $this->dateTime()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-audit-logs-user',
            '{{%audit_logs}}',
            'user_id',
            '{{%users}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-audit-logs-file',
            '{{%audit_logs}}',
            'file_id',
            '{{%files}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-audit-logs-folder',
            '{{%audit_logs}}',
            'folder_id',
            '{{%folders}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->createIndex(
            'idx-audit-logs-user_id',
            '{{%audit_logs}}',
            'user_id'
        );

        $this->createIndex(
            'idx-audit-logs-file_id',
            '{{%audit_logs}}',
            'file_id'
        );

        $this->createIndex(
            'idx-audit-logs-folder_id',
            '{{%audit_logs}}',
            'folder_id'
        );

        $this->createIndex(
            'idx-audit-logs-action',
            '{{%audit_logs}}',
            'action'
        );

        $this->createIndex(
            'idx-audit-logs-created_at',
            '{{%audit_logs}}',
            'created_at'
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%audit_logs}}');
        $this->dropTable('{{%file_shares}}');
    }
}
