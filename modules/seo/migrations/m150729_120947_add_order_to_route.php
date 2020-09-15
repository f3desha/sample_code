<?php
/**
 * Created by PhpStorm.
 * User: wa1
 * Date: 29.09.15
 * Time: 15:33
 */

use yii\db\Schema;
use yii\db\Migration;

class m150729_120947_add_order_to_route extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%route}}', 'order', Schema::TYPE_INTEGER . ' NOT NULL');
    }

    public function safeDown()
    {
        $this->dropColumn('{{%route}}', 'order');
    }

}