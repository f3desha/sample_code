<?php

use yii\db\Migration;

/**
 * Class m181212_101034_alter_lead_add_identifier
 */
class m181212_101034_alter_lead_add_identifier extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->addColumn('{{%dealerinventory_ignite_leads}}', 'tracking_identifier', $this->string());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumn('{{%dealerinventory_ignite_leads}}', 'tracking_identifier');
	}

}
