<?php

use yii\db\Migration;

/**
 * Class m181222_091359_add_event_subdomain_dealer_id
 */
class m181222_091359_add_event_subdomain_dealer_id extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->addColumn('{{%tracking_session}}', 'event_subdomain_dealer_id', $this->integer()->after('event_id')->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumn('{{%tracking_session}}', 'event_subdomain_dealer_id');
    }


}
