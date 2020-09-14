<?php

use yii\db\Migration;

/**
 * Class m190108_112429_adjust_last_payment_fields
 */
class m190108_112429_adjust_last_payment_fields extends Migration
{
	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$this->alterColumn('{{%tracking_last_payment_event}}', 'msrp', $this->double()->null());
		$this->alterColumn('{{%tracking_last_payment_event}}', 'discount', $this->double()->null());
		$this->alterColumn('{{%tracking_last_payment_event}}', 'total_rebates', $this->double()->null());
		$this->alterColumn('{{%tracking_last_payment_event}}', 'finance_discount', $this->double()->null());
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$this->alterColumn('{{%tracking_last_payment_event}}', 'finance_discount', $this->double()->null());
		$this->alterColumn('{{%tracking_last_payment_event}}', 'msrp', $this->integer()->null());
		$this->alterColumn('{{%tracking_last_payment_event}}', 'discount', $this->integer()->null());
		$this->alterColumn('{{%tracking_last_payment_event}}', 'total_rebates', $this->integer()->null());

	}
}
