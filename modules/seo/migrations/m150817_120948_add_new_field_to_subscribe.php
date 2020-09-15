<?php

use yii\db\Schema;
use yii\db\Migration;

class m150817_120948_add_new_field_to_subscribe extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%subscribe}}', 'params', Schema::TYPE_STRING . '(4096) ');

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('{{%subscribe}}', 'params');
    }
}
