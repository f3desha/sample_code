<?php

use yii\db\Migration;

/**
 * Class m180712_070740_add_zip_for_lead
 */
class m180712_070740_add_zip_for_lead extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%dealerinventory_ignite_leads}}', 'zip', $this->char(5)->defaultValue(NULL));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%dealerinventory_ignite_leads}}', 'zip');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180712_070740_add_zip_for_lead cannot be reverted.\n";

        return false;
    }
    */
}
