<?php

use yii\db\Migration;

/**
 * Class m181221_091731_alter_last_payment_table
 */
class m181221_091731_alter_last_payment_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->alterColumn('{{%tracking_last_payment_event}}', 'payment_type', $this->text()->null());
		$this->alterColumn('{{%tracking_last_payment_event}}', 'msrp', $this->integer()->null());
		$this->alterColumn('{{%tracking_last_payment_event}}', 'discount', $this->integer()->null());
		$this->alterColumn('{{%tracking_last_payment_event}}', 'total_rebates', $this->integer()->null());
		$this->alterColumn('{{%tracking_last_payment_event}}', 'term', $this->integer()->null());
		$this->alterColumn('{{%tracking_last_payment_event}}', 'miles_per_year', $this->integer()->null());
		$this->alterColumn('{{%tracking_last_payment_event}}', 'due_on_signing', $this->integer()->null());
		$this->alterColumn('{{%tracking_last_payment_event}}', 'monthly_price', $this->double()->null());
		$this->addColumn('{{%tracking_last_payment_event}}', 'finance_discount', $this->integer()->null());
		$this->addColumn('{{%tracking_last_payment_event}}', 'finance_term', $this->integer()->null());
		$this->addColumn('{{%tracking_last_payment_event}}', 'finance_due_on_signing', $this->integer()->null());
		$this->addColumn('{{%tracking_last_payment_event}}', 'finance_monthly_price', $this->double()->null());
	}

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$this->dropColumn('{{%tracking_last_payment_event}}', 'finance_discount');
		$this->dropColumn('{{%tracking_last_payment_event}}', 'finance_term');
		$this->dropColumn('{{%tracking_last_payment_event}}', 'finance_due_on_signing');
		$this->dropColumn('{{%tracking_last_payment_event}}', 'finance_monthly_price');
		$this->alterColumn('{{%tracking_last_payment_event}}', 'msrp', $this->integer()->notNull());
		$this->alterColumn('{{%tracking_last_payment_event}}', 'discount', $this->integer()->notNull());
		$this->alterColumn('{{%tracking_last_payment_event}}', 'total_rebates', $this->integer()->notNull());
		$this->alterColumn('{{%tracking_last_payment_event}}', 'term', $this->integer()->notNull());
		$this->alterColumn('{{%tracking_last_payment_event}}', 'miles_per_year', $this->integer()->notNull());
		$this->alterColumn('{{%tracking_last_payment_event}}', 'due_on_signing', $this->integer()->notNull());
		$this->alterColumn('{{%tracking_last_payment_event}}', 'monthly_price', $this->double()->notNull());
		$this->alterColumn('{{%tracking_last_payment_event}}', 'payment_type', $this->integer()->notNull());

	}


}
