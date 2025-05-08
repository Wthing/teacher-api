<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%data}}`.
 */
class m250425_122326_create_data_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('data', [
            'id' => $this->primaryKey(),
            'profile_id' => $this->integer()->notNull(),
            'field_id' => $this->integer()->notNull(),
            'data' => $this->text()->notNull(),
        ]);

        $this->createIndex('idx-data_profile_id', 'data', 'profile_id');
        $this->createIndex('idx-data_field_id', 'data', 'field_id');

        $this->addForeignKey('fk_data_profile', 'data', 'profile_id', 'profiles', 'id');
        $this->addForeignKey('fk_data_field', 'data', 'field_id', 'form_fields', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_data_profile', 'data');
        $this->dropIndex('idx-data_profile_id', 'data');

        $this->dropForeignKey('fk_data_field', 'data');
        $this->dropIndex('idx-data_field_id', 'data');

        $this->dropTable('data');
    }
}
