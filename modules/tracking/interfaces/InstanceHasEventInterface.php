<?php

namespace modules\tracking\interfaces;

use modules\tracking\models\backend\TrackingSession;

/**
 * Interface InstanceHasEventInterface
 * @package modules\tracking\interfaces
 */
interface InstanceHasEventInterface
{
    /**
     * @param \modules\tracking\models\backend\TrackingSession $trackingSession
     * @param array $event_group_settings
     *
     * @return mixed
     */
    public function assign(TrackingSession $trackingSession, array $event_group_settings);
}