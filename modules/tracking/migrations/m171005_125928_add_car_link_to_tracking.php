<?php

use yii\db\Migration;

/**
 * Class m171005_125928_add_car_link_to_tracking
 */
class m171005_125928_add_car_link_to_tracking extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%tracking}}', 'car_link', $this->text()->after('link_to'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('{{%tracking}}', 'car_link');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m171005_125928_add_car_link_to_tracking cannot be reverted.\n";

        return false;
    }
    */
}
