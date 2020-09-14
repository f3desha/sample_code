<?php

use yii\db\Migration;

/**
 * Handles the creation of table `tracking`.
 */
class m170927_122340_create_tracking_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // MySql table options
        $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';


        $this->createTable(
            '{{%tracking}}',
            [
                'id' => $this->primaryKey(),
                'cookie_user_id' => $this->text(),
                'page_id' => $this->string(255),
                'lead_id' => $this->integer(),
                'ip_address' => $this->string(255),
                'link_from' => $this->text(),
                'link_to' => $this->text(),
                'duration' => $this->float(),
                'created_at' => $this->integer()->notNull(),
                'updated_at' => $this->integer()->Null(),
            ],
            $tableOptions
        );

        $this->createIndex( 'idx-lead_id', '{{%dealerinventory_ignite_leads}}', 'id');

    }

    public function safeDown()
    {
        $this->dropTable('{{%tracking}}');
    }

}
