<?php

use yii\db\Migration;

class m260824_203838_create_folders_and_files_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        /*
         * Folders
         *
         * Supports:
         * Location → Department → User → nested folders
         */
        $this->createTable('{{%folders}}', [
            'id' => $this->bigPrimaryKey(),

            'parent_id' => $this->bigInteger()->null(),

            'location_id' => $this->integer()->null(),

            'department_id' => $this->integer()->null(),

            'owner_user_id' => $this->integer()->null(),

            'name' => $this->string(255)->notNull(),

            'folder_type' => $this->string(30)->notNull()->defaultValue('custom'),

            'created_by' => $this->integer()->notNull(),

            'created_at' => $this->dateTime()->notNull(),

            'updated_at' => $this->dateTime()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-folders-parent_id',
            '{{%folders}}',
            'parent_id',
            '{{%folders}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-folders-location_id',
            '{{%folders}}',
            'location_id',
            '{{%locations}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-folders-department_id',
            '{{%folders}}',
            'department_id',
            '{{%departments}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-folders-owner_user_id',
            '{{%folders}}',
            'owner_user_id',
            '{{%users}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-folders-created_by',
            '{{%folders}}',
            'created_by',
            '{{%users}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->createIndex(
            'idx-folders-parent_id',
            '{{%folders}}',
            'parent_id'
        );

        $this->createIndex(
            'idx-folders-location_id',
            '{{%folders}}',
            'location_id'
        );

        $this->createIndex(
            'idx-folders-department_id',
            '{{%folders}}',
            'department_id'
        );

        $this->createIndex(
            'idx-folders-owner_user_id',
            '{{%folders}}',
            'owner_user_id'
        );

        /*
         * Files
         *
         * Stores metadata only.
         * Physical files will live in MinIO/self-hosted storage.
         */
        $this->createTable('{{%files}}', [
            'id' => $this->bigPrimaryKey(),

            'folder_id' => $this->bigInteger()->notNull(),

            'uploader_id' => $this->integer()->notNull(),

            'location_id' => $this->integer()->null(),

            'department_id' => $this->integer()->null(),

            'name' => $this->string(255)->notNull(),

            'original_name' => $this->string(255)->notNull(),

            'storage_key' => $this->string(512)->notNull(),

            'mime_type' => $this->string(150)->notNull(),

            'extension' => $this->string(20)->null(),

            'size_bytes' => $this->bigInteger()->notNull(),

            'description' => $this->text()->null(),

            'tags' => $this->text()->null(),

            'checksum' => $this->string(128)->null(),

            'status' => $this->string(30)->notNull()->defaultValue('active'),

            'uploaded_at' => $this->dateTime()->notNull(),

            'updated_at' => $this->dateTime()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-files-folder_id',
            '{{%files}}',
            'folder_id',
            '{{%folders}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-files-uploader_id',
            '{{%files}}',
            'uploader_id',
            '{{%users}}',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-files-location_id',
            '{{%files}}',
            'location_id',
            '{{%locations}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-files-department_id',
            '{{%files}}',
            'department_id',
            '{{%departments}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->createIndex(
            'idx-files-folder_id',
            '{{%files}}',
            'folder_id'
        );
        
        $this->createIndex(
            'idx-files-storage_key',
            '{{%files}}',
            'storage_key'
        );

        $this->createIndex(
            'idx-files-uploader_id',
            '{{%files}}',
            'uploader_id'
        );

        $this->createIndex(
            'idx-files-location_id',
            '{{%files}}',
            'location_id'
        );

        $this->createIndex(
            'idx-files-department_id',
            '{{%files}}',
            'department_id'
        );

        $this->createIndex(
            'idx-files-mime_type',
            '{{%files}}',
            'mime_type'
        );

        $this->createIndex(
            'idx-files-uploaded_at',
            '{{%files}}',
            'uploaded_at'
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%files}}');
        $this->dropTable('{{%folders}}');
    }
}