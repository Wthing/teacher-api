<?php

namespace app\migrations;

use yii\db\Migration;

/**
 * Handles the creation of table `{{%form_field_autocomplete}}`.
 */
class m250425_122515_create_form_field_autocomplete_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('form_field_autocomplete', [
            'id' => $this->primaryKey(),
            'field_id' => $this->integer()->notNull(),
            'content' => $this->string()->notNull(),
        ]);

        $this->createIndex('idx-form_field_autocomplete_field_id', 'form_field_autocomplete', 'field_id');

        $this->addForeignKey('fk_form_field_autocomplete_field', 'form_field_autocomplete', 'field_id', 'form_fields', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_form_field_autocomplete_field', 'form_field_autocomplete');
        $this->dropIndex('idx-form_field_autocomplete_field_id', 'form_field_autocomplete');
        $this->dropTable('form_field_autocomplete');
    }
}
