<?php

use yii\db\Migration;

/**
 * Class m171018_103426_add_cookie_user_id_to_leads
 */
class m171018_103426_add_cookie_user_id_to_leads extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%dealerinventory_ignite_leads}}', 'cookie_user_id', $this->text()->after('user_id')->defaultValue(null));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('{{%dealerinventory_ignite_leads}}', 'cookie_user_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m171018_103426_add_cookie_user_id_to_leads cannot be reverted.\n";

        return false;
    }
    */
}
