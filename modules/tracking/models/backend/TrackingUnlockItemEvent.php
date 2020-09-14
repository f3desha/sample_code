<?php

namespace modules\tracking\models\backend;

use modules\tracking\interfaces\TrackingEventInterface;

/**
 * Class TrackingUnlockItemEvent
 * @package modules\tracking\models\backend
 * We should have group of allowed tracking actions for running if tracking session ready for tracking
 */
class TrackingUnlockItemEvent implements TrackingEventInterface
{

    /**
     * @var bool
     */
    private $screen_incrementing = false;


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [

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
     * @return bool
     */
    public function getScreenIncrementing()
    {
        return $this->screen_incrementing;
    }

    /**
     * @param \modules\tracking\models\backend\TrackingSession $trackingSession
     * @param array $event_params
     * @param array $optional_params
     */
    public function registerEvent(TrackingSession $trackingSession, array $event_params, array $optional_params = [])
    {
        try {

            if (!empty($optional_params['custom_method_name'])) {
                if (method_exists($this, $optional_params['custom_method_name'])) {
                    $function_name = $optional_params['custom_method_name'];
                    $this->$function_name();
                }
            }

        } catch (\ErrorException $e) {
            var_dump($e->getMessage());
            exit();
        }
    }

    /**
     * @inheritdoc
     */
    public function getTrackingSession()
    {
        return $this->hasOne(TrackingSession::className(), ['unique_event_id' => 'event_id']);
    }

}