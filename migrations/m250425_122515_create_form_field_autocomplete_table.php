<?php

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
        $this->createTable('{{%form_field_autocomplete}}', [
            'id' => $this->primaryKey(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%form_field_autocomplete}}');
    }
}
