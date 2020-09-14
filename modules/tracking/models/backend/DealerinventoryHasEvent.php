<?php namespace modules\tracking\models\backend;

use modules\dealerinventory\models\backend\Dealerinventory;
use modules\tracking\interfaces\InstanceHasEventInterface;

/**
 * Class DealerinventoryHasEvent
 * @package modules\tracking\models\backend
 *
 * @property \yii\db\ActiveQuery $event
 * @property \yii\db\ActiveQuery $dealerinventory
 * @property int $id [int(11)]
 * @property string $tracking_identifier [varchar(255)]
 * @property string $event_id [varchar(255)]
 * @property int $ignite_item_id [int(11)]
 */
class DealerinventoryHasEvent extends InstanceHasEvent implements InstanceHasEventInterface
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%tracking_dealerinventory_has_events}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ignite_item_id'], 'integer'],
            [['event_id', 'tracking_identifier'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [

        ];
    }

    /**
     * @param \modules\tracking\models\backend\TrackingSession $trackingSession
     * @param array $event_group_settings
     */
    public function assign(TrackingSession $trackingSession, array $event_group_settings)
    {
        $this->tracking_identifier = $trackingSession->tracking_identifier;
        $this->event_id = $trackingSession->event_id;
        $this->ignite_item_id = $event_group_settings['ignite_item_id'];
        $this->save();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDealerinventory()
    {
        return $this->hasOne(Dealerinventory::className(), ['id' => 'ignite_item_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEvent()
    {
        return $this->hasOne(TrackingSession::className(), ['event_id' => 'event_id']);
    }

}
