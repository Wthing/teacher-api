<?php

namespace app\migrations;

use yii\db\Migration;

/**
 * Handles the creation of table `{{%form_fields}}`.
 */
class m250425_122443_create_form_fields_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('form_fields', [
            'id' => $this->primaryKey(),
            'form_id' => $this->integer()->notNull(),
            'type_id' => $this->integer()->notNull(),
            'field_name' => $this->string()->notNull(),
            'status' => $this->boolean()->notNull()->defaultValue(0),
        ]);

        $this->createIndex('idx-form_fields_type_id', 'form_fields', 'type_id');
        $this->createIndex('idx-form_fields_form_id', 'form_fields', 'form_id');

        $this->addForeignKey('fk_form_fields_type', 'form_fields', 'type_id', 'form_fields_type', 'id', 'CASCADE');
        $this->addForeignKey('fk_form_fields_form', 'form_fields', 'form_id', 'forms', 'id', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_form_fields_type', 'form_fields');
        $this->dropIndex('idx-form_fields_type_id', 'form_fields');

        $this->dropForeignKey('fk_form_fields_form', 'form_fields');
        $this->dropIndex('idx-form_fields_form_id', 'form_fields');
        $this->dropTable('form_fields');
    }
}
