<?php

namespace app\migrations;

use yii\db\Migration;

class m250605_044810_create_form_confirm_application extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('form_confirm_application', [
            'id' => $this->primaryKey(),
            'record_index' => $this->integer()->notNull(),
            'created_by' => $this->integer()->notNull(),
            'assigned_to' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'confirmed_at' => $this->integer()->null(),
            'status' => $this->integer()->notNull()->defaultValue(0),
        ]);

        $this->createIndex('idx_form_confirm_application_created_by', 'form_confirm_application', 'created_by');
        $this->createIndex('idx_form_confirm_application_assigned_to', 'form_confirm_application', 'assigned_to');

        $this->addForeignKey('fk_form_confirm_application_created_by', 'form_confirm_application', 'created_by', 'user', 'id');
        $this->addForeignKey('fk_form_confirm_application_assigned_to', 'form_confirm_application', 'assigned_to', 'user', 'id');

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_form_confirm_application_created_by', 'form_confirm_application');
        $this->dropIndex('idx_form_confirm_application_created_by', 'form_confirm_application');

        $this->dropForeignKey('fk_form_confirm_application_assigned_to', 'form_confirm_application');
        $this->dropIndex('idx_form_confirm_application_assigned_to', 'form_confirm_application');

        $this->dropTable('form_confirm_application');
    }
}
