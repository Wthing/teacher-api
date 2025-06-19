<?php

use mdm\admin\components\Configs;
use yii\db\Migration;

class m150619_053454_change_email_nullable extends Migration
{
    public function safeUp()
    {
        $userTable = Configs::instance()->userTable;

        // Меняем поле 'email' на nullable
        $this->alterColumn($userTable, 'email', $this->string()->null());
    }

    public function safeDown()
    {
        $userTable = Configs::instance()->userTable;

        // Возвращаем обратно как NOT NULL
        $this->alterColumn($userTable, 'email', $this->string()->notNull());
    }
}