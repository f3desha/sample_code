<?php

namespace modules\tracking\interfaces;

use modules\tracking\models\backend\TrackingSession;

/**
 * Interface TrackingEventInterface
 * @package modules\tracking\interfaces
 */
interface TrackingEventInterface
{
    /**
     * @param \modules\tracking\models\backend\TrackingSession $trackingSession
     * @param array $event_params
     * @param array $optional_params
     *
     * @return mixed
     */
    public function registerEvent(TrackingSession $trackingSession, array $event_params, array $optional_params);

    /**
     * @return mixed
     */
    public function getScreenIncrementing();
}