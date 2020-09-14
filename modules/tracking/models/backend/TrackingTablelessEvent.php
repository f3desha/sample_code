<?php

namespace modules\tracking\models\backend;

use yii\base\Model;
use modules\tracking\interfaces\TrackingEventInterface;

/**
 * Class TrackingTablelessEvent
 * @package modules\tracking\models\backend
 *
 * @property bool $screenIncrementing
 */
class TrackingTablelessEvent extends Model implements TrackingEventInterface
{

    /**
     * @var bool
     */
    private $screen_incrementing = true;

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
     *
     */
    public function onPageLoad()
    {

    }

    /**
     * @param \modules\tracking\models\backend\TrackingSession $trackingSession
     * @param array $event_params
     * @param array $optional_params
     */
    public function registerEvent(TrackingSession $trackingSession, array $event_params, array $optional_params)
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

}
