<?php

use yii\db\Migration;

class m140619_064006_add_status_verification_to_forms extends Migration
{
    public function safeUp()
    {
        $table = 'forms';

        $schema = Yii::$app->db->schema;

        if (!$schema->getTableSchema($table, true)->getColumn('status')) {
            $this->addColumn($table, 'status', $this->integer()->notNull()->defaultValue(1)->after('form_name'));
        }

        if (!$schema->getTableSchema($table, true)->getColumn('requires_verification')) {
            $this->addColumn($table, 'requires_verification', $this->boolean()->defaultValue(0)->after('form_name'));
        }
    }

    public function safeDown()
    {
        $table = 'forms';

        if (Yii::$app->db->schema->getTableSchema($table, true)->getColumn('status')) {
            $this->dropColumn($table, 'status');
        }

        if (Yii::$app->db->schema->getTableSchema($table, true)->getColumn('requires_verification')) {
            $this->dropColumn($table, 'requires_verification');
        }
    }
}
