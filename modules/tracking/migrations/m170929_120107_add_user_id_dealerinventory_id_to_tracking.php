<?php

use yii\db\Migration;

/**
 * Class m170929_120107_add_user_id_dealerinventory_id_to_tracking
 */
class m170929_120107_add_user_id_dealerinventory_id_to_tracking extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%tracking}}', 'dealerinventory_id', $this->integer()->after('cookie_user_id'));
        $this->addColumn('{{%tracking}}', 'user_id', $this->integer()->after('cookie_user_id'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('{{%tracking}}', 'dealerinventory_id');
        $this->dropColumn('{{%tracking}}', 'user_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m170929_120107_add_user_id_dealerinventory_id_to_tracking cannot be reverted.\n";

        return false;
    }
    */
}
