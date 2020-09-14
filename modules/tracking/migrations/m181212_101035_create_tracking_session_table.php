<?php

use yii\db\Migration;

/**
 * Handles the creation of table `tracking_session`.
 */
class m181212_101035_create_tracking_session_table extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		$tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';


		$this->createTable('{{%tracking_session}}', [
			'id' => $this->primaryKey(),
			'tracking_identifier' => $this->string()->notNull(),
			'event_id' => $this->string()->unique()->notNull(),
			'event_name' => $this->string()->notNull()->defaultValue(''),
			'event_description' => $this->text(),
			'event_type' => $this->integer()->notNull(),
			'screen_id' => $this->integer()->notNull(),
			'screen_url' => $this->text()->notNull(),
			'event_time' => $this->string()->notNull(),
			'event_ip' => $this->string()->notNull()
		],
			$tableOptions);

		$this->createIndex('idx-eventid-id', '{{%tracking_session}}', 'event_id');
		//Foreign Keys
		//$this->addForeignKey('fk-dealerin-dealerin-has-standard', '{{%dealerinventory_has_standards}}', 'dealerinventory_id', '{{%dealerinventory}}', 'id', 'CASCADE', 'CASCADE');
		//$this->addPrimaryKey('primary-id-type-key', '{{%tracking_session}}', ['event_id','event_type']);

	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		$this->dropTable('{{%tracking_session}}');
	}
}
