<?php

namespace app\migrations;

use yii\db\Migration;

/**
 * Handles the creation of table `{{%form_fields_type}}`.
 */
class m250425_122433_create_form_fields_type_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('form_fields_type', [
            'id' => $this->primaryKey(),
            'type_name' => $this->string()->notNull(),
            'status' => $this->boolean()->notNull()->defaultValue(0),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('form_fields_type');
    }
}
