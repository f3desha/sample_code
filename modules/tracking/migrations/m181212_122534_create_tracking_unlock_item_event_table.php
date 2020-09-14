<?php

use yii\db\Migration;

/**
 * Handles the creation of table `tracking_unlock_item_event`.
 */
class m181212_122534_create_tracking_unlock_item_event_table extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp()
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

	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		$this->dropTable('{{%tracking_unlock_item_event}}');
	}
}
