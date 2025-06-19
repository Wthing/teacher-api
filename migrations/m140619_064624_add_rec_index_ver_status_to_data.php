<?php

use yii\db\Migration;

class m140619_064624_add_rec_index_ver_status_to_data extends Migration
{
    public function safeUp()
    {
        $table = 'data';
        $schema = Yii::$app->db->schema;

        // Добавим record_index, если нет
        if (!$schema->getTableSchema($table, true)->getColumn('record_index')) {
            $this->addColumn($table, 'record_index', $this->integer()->after('data'));
        }

        // Добавим verification_status, если нет
        if (!$schema->getTableSchema($table, true)->getColumn('verification_status')) {
            $this->addColumn($table, 'verification_status', $this->integer()->defaultValue(0)->after('record_index'));
        }
    }

    public function safeDown()
    {
        $table = 'data';

        if (Yii::$app->db->schema->getTableSchema($table, true)->getColumn('verification_status')) {
            $this->dropColumn($table, 'verification_status');
        }

        if (Yii::$app->db->schema->getTableSchema($table, true)->getColumn('record_index')) {
            $this->dropColumn($table, 'record_index');
        }
    }
}
