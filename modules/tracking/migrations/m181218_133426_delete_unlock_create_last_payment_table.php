<?php

use yii\db\Migration;

/**
 * Class m181218_133426_delete_unlock_create_last_payment_table
 */
class m181218_133426_delete_unlock_create_last_payment_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
		$this->dropTable('{{%tracking_unlock_item_event}}');
		$tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';

		$this->createTable('{{%tracking_last_payment_event}}', [
			'id' => $this->primaryKey(),
			'unique_event_id' => $this->string()->unique()->notNull(),
			'payment_type' => $this->integer()->notNull(),
			'msrp' => $this->integer()->notNull(),
			'discount' => $this->integer()->notNull(),
			'total_rebates' => $this->integer()->notNull(),
			'term' => $this->integer()->notNull(),
			'miles_per_year' => $this->integer()->notNull(),
			'due_on_signing' => $this->integer()->notNull(),
			'monthly_price' => $this->double()->notNull(),
		],
			$tableOptions);

		$this->createIndex('idx-lastpayment-id', '{{%tracking_last_payment_event}}', 'unique_event_id');

		//Foreign Keys
		$this->addForeignKey('fk-session-event2',
			'{{%tracking_last_payment_event}}', 'unique_event_id',
			'{{%tracking_session}}','event_id',
			'CASCADE', 'CASCADE');

	}

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
		$tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';

		$this->createTable('{{%tracking_unlock_item_event}}', [
			'id' => $this->primaryKey(),
			'unique_event_id' => $this->string()->unique()->notNull(),
			'ignite_item_id' => $this->integer()->notNull()
		],
			$tableOptions);

		$this->createIndex('idx-dealerinven-id', '{{%tracking_unlock_item_event}}', 'ignite_item_id');
		$this->createIndex('idx-even-id', '{{%tracking_unlock_item_event}}', 'unique_event_id');

		//Foreign Keys
		$this->addForeignKey('fk-tracking-session-ignite-id', '{{%tracking_unlock_item_event}}', 'ignite_item_id', '{{%dealerinventory}}', 'id', 'CASCADE', 'CASCADE');

		$this->addForeignKey('fk-session-event1',
			'{{%tracking_unlock_item_event}}', 'unique_event_id',
			'{{%tracking_session}}','event_id',
			'CASCADE', 'CASCADE');
		$this->dropTable('{{%tracking_unlock_item_event}}');

	}

}
