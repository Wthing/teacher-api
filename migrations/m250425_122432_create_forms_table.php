<?php

namespace app\migrations;

use yii\db\Migration;

/**
 * Handles the creation of table `{{%forms}}`.
 */
class m250425_122432_create_forms_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('forms', [
            'id' => $this->primaryKey(),
            'form_name' => $this->string()->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('forms');
    }
}
