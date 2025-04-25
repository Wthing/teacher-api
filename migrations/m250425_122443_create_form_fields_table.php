<?php

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
        $this->createTable('{{%form_fields}}', [
            'id' => $this->primaryKey(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%form_fields}}');
    }
}
