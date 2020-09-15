<?php

use yii\db\Schema;
use yii\db\Migration;

class m150815_120946_add_new_field_to_route extends Migration
{
    public function safeUp()
    {
    $this->addColumn('{{%route}}', 'cached', Schema::TYPE_BOOLEAN . ' NOT NULL');
    }

    /**
    * @inheritdoc
    */
    public function safeDown()
    {
    $this->dropColumn('{{%route}}', 'cached');
    }
}