<?php

use yii\db\Schema;
use yii\db\Migration;

class m150816_120947_create_table_subscribe extends Migration
{
    public function up()
    {
        // MySql table options
        $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';

        // Users table
        $this->createTable(
            '{{%subscribe}}',
            [
                'id' => Schema::TYPE_PK,
                'route_id' => Schema::TYPE_INTEGER . ' NOT NULL',
                'email' => Schema::TYPE_STRING . '(320) NOT NULL',
            ],
            $tableOptions
        );

    }

    public function down()
    {
        $this->dropTable('{{%subscribe}}');
        echo "m150816_120947_create_table_subscribe cannot be reverted.\n";

        return false;
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }
    
    public function safeDown()
    {
    }
    */
}
