<?php

namespace app\migrations;

use yii\db\Migration;

class m250605_044141_create_form_confirm_person extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('form_confirm_person', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'form_id' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_form_confirm_person_user_id', 'form_confirm_person', 'user_id');
        $this->createIndex('idx_form_confirm_person_form_id', 'form_confirm_person', 'form_id');

        $this->addForeignKey('fk_form_confirm_person_user_id', 'form_confirm_person', 'user_id', 'user', 'id');
        $this->addForeignKey('fk_form_confirm_person_form_id', 'form_confirm_person', 'form_id', 'forms', 'id');

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_form_confirm_person_user_id', 'form_confirm_person');
        $this->dropIndex('idx_form_confirm_person_user_id', 'form_confirm_person');

        $this->dropForeignKey('fk_form_confirm_person_form_id', 'form_confirm_person');
        $this->dropIndex('idx_form_confirm_person_form_id', 'form_confirm_person');

        $this->dropTable('form_confirm_person');
    }
}
