<?php
/**
 * Created by PhpStorm.
 * User: wa1
 * Date: 23.09.15
 * Time: 10:47
 */
use yii\db\Schema;
use yii\db\Migration;

class m150818_141121_add_new_field_to_route extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%route}}', 'results', Schema::TYPE_INTEGER . ' NOT NULL');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('{{%route}}', 'main');
    }
}
