<?php

use yii\db\Migration;

/**
 * Class m181217_125037_create_tracking_dealerinventory_has_events
 */
class m181217_125037_create_tracking_dealerinventory_has_events extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		$tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';


		$this->createTable('{{%tracking_dealerinventory_has_events}}', [
			'id' => $this->primaryKey(),
			'tracking_identifier' => $this->string()->notNull(),
			'event_id' => $this->string()->notNull(),
			'ignite_item_id' => $this->integer()->notNull()
		],
			$tableOptions);

		$this->createIndex('idx-evend-id', '{{%tracking_dealerinventory_has_events}}', 'event_id');

		//Foreign Keys
		$this->addForeignKey('fk-session-devent',
			'{{%tracking_dealerinventory_has_events}}', 'event_id',
			'{{%tracking_session}}','event_id',
			'CASCADE', 'CASCADE');

	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		$this->dropTable('{{%tracking_dealerinventory_has_events}}');
	}
}
