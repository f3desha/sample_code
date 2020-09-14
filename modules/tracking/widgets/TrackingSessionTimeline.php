<?php

namespace modules\tracking\widgets;

use modules\tracking\models\backend\TrackingSession;
use yii\base\Widget;

/**
 * Class TrackingSessionTimeline
 * @package modules\tracking\widgets
 */
class TrackingSessionTimeline extends Widget
{
    /**
     * @var
     */
    public $models;

    /**
     *
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @param $event
     *
     * @return mixed
     */
    public static function getEventName($event)
    {
        return empty($event->event_name) ? TrackingSession::EVENT_TYPES[$event->event_type]['eventName'] : $event->event_name;
    }

    /**
     * @param $name
     * @param $event
     *
     * @return string
     */
    public static function getLastPaymentField($name, $event)
    {
    	if(property_exists($event, 'lastPaymentEvent')){
			$all_types = explode(',', $event->lastPaymentEvent->payment_type);
			$last = $all_types[count($all_types) - 1];

			switch ($name) {
				case 'monthly_price':
					if ($last === "1") {
						return self::getLastPaymentDataByType($last, $event, 'monthly_price');
					} elseif ($last === "2") {
						return self::getLastPaymentDataByType($last, $event, 'finance_monthly_price');
					}
					break;
				case 'due_on_signing':
					if ($last === "1") {
						return self::getLastPaymentDataByType($last, $event, 'due_on_signing');
					} elseif ($last === "2") {
						return self::getLastPaymentDataByType($last, $event, 'finance_due_on_signing');
					}
					break;
			}	
		}
    }

    /**
     * @param $type
     * @param $event
     * @param bool $field_name
     *
     * @return string
     */
    public static function getLastPaymentDataByType($type, $event, $field_name = false)
    {
        $OUTPUT = '';
        if ($field_name) {
            return $event->lastPaymentEvent->$field_name;
        }

        switch ($type) {
            case 1:
                $OUTPUT = <<<OUTPUT
<div class="col-sm-4 invoice-col">
					<b>Payment Type:</b> Lease<br>
					<b>MSRP:</b> {$event->lastPaymentEvent->msrp}<br>
					<b>Lease Discount:</b> {$event->lastPaymentEvent->discount}<br>
					<b>Lease Total Rebates:</b> {$event->lastPaymentEvent->total_rebates}<br>
				</div>
				<div class="col-sm-4 invoice-col">
					<b>Payment:</b> {$event->lastPaymentEvent->monthly_price}<br>
					<b>Lease Term:</b> {$event->lastPaymentEvent->term}<br>
					<b>Lease Miles Per Year:</b> {$event->lastPaymentEvent->miles_per_year}<br>
					<b>Lease Due on Signing:</b> {$event->lastPaymentEvent->due_on_signing}<br>
				</div>
OUTPUT;
                break;
            case 2:
                $OUTPUT = <<<OUTPUT
<div class="col-sm-4 invoice-col">
					<b>Payment Type:</b> Finance<br>
					<b>MSRP:</b> {$event->lastPaymentEvent->msrp}<br>
					<b>Finance Discount:</b> {$event->lastPaymentEvent->finance_discount}<br>
				</div>
				<div class="col-sm-4 invoice-col">
					<b>Finance Payment:</b> {$event->lastPaymentEvent->finance_monthly_price}<br>
					<b>Finance Term:</b> {$event->lastPaymentEvent->finance_term}<br>
					<b>Finance Due on Signing:</b> {$event->lastPaymentEvent->finance_due_on_signing}<br>
				</div>
OUTPUT;
                break;
        }

        return $OUTPUT;
    }

    /**
     * @param $dataProvider
     *
     * @return array
     */
    public function combineEventsInScreens($dataProvider)
    {
        $screens = [];

        foreach ($this->models as $key => $event) {

            if (empty($screens)) {
                $previous_event = $event;
            } else {
                end($screens);
                $last_screen_key = key($screens);

                end($screens[$last_screen_key]);
                $last_internal_key = key($screens[$last_screen_key]);

                $previous_event = $screens[$last_screen_key][$last_internal_key];
            }

            $event->assignDuration($previous_event);
            $screens[$event['screen_id']][] = $event;

        }

        return $screens;
    }

    /**
     * @param $event
     *
     * @return string
     */
    public static function renderEvent($event)
    {
        return 'eventTemplates/' . TrackingSession::EVENT_TYPES[$event->event_type]['widgetTemplateName'];
    }

    /**
     * @return string
     */
    public function run()
    {
        //Есть 24 ивента
        //Объединить выборку из 24 ивентов в Скрины. Оказалось что у нас 4 Скрина по 6 ивентов
        $screens_collection = $this->combineEventsInScreens($this->models);

        //Для каждого скрина
        //закинуть Скрин в Скрин процессор

        //В скрин процессоре для каждого ивента отрендерить определенный шаблон ивента

        return $this->render('timeline_wrapper', [
            'screens_collection' => $screens_collection,
        ]);
    }
}
