<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%form_fields_type}}`.
 */
class m250425_122455_create_form_fields_type_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%form_fields_type}}', [
            'id' => $this->primaryKey(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%form_fields_type}}');
    }
}
