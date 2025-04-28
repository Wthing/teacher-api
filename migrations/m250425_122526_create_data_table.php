<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%data}}`.
 */
class m250425_122526_create_data_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('data', [
            'id' => $this->primaryKey(),
            'profile_id' => $this->integer()->notNull(),
            'form_id' => $this->integer()->notNull(),
            'data' => $this->text()->notNull(),
        ]);

        $this->createIndex('idx-data_profile_id', 'data', 'profile_id');
        $this->createIndex('idx-data_form_id', 'data', 'form_id');

        $this->addForeignKey('fk_data_profile', 'data', 'profile_id', 'profiles', 'id');
        $this->addForeignKey('fk_data_form', 'data', 'form_id', 'forms', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_form_fields_profile', 'form_fields');
        $this->dropIndex('idx-data_profile_id', 'form_fields');

        $this->dropForeignKey('fk_form_fields_form', 'form_fields');
        $this->dropIndex('idx-data_form_id', 'form_fields');

        $this->dropTable('data');
    }
}
