<?php

namespace modules\tracking\models\backend;

use common\components\dataproviders\EncryptedDataProvider;
use common\components\F3deshaHelpers;
use GuzzleHttp\Client;
use modules\api\controllers\DefaultController;
use modules\dealerinventory\models\backend\Dealerinventory;
use modules\dealerinventory\models\backend\IgniteLead;
use modules\tracking\interfaces\TrackingEventInterface;
use modules\zipdata\models\Zip;
use Yii;
use yii\base\ErrorException;
use yii\base\Event;
use yii\base\Model;
use yii\data\Pagination;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Class TrackingSession
 * @package modules\tracking\models\backend
 * We should have group of allowed tracking actions for running if tracking session ready for tracking
 *
 * @property mixed $durationInSeconds
 * @property mixed $timeFromPrevious
 * @property mixed $lastPaymentEvent
 * @property mixed $geolocationZipManual
 * @property int $id [int(11)]
 * @property string $tracking_identifier [varchar(255)]
 * @property string $event_id [varchar(255)]
 * @property int $event_subdomain_dealer_id [int(11)]
 * @property string $event_name [varchar(255)]
 * @property string $event_description
 * @property int $event_type [int(11)]
 * @property int $screen_id [int(11)]
 * @property string $screen_url
 * @property string $event_time [varchar(255)]
 * @property string $event_ip [varchar(255)]
 */
class TrackingSession extends ActiveRecord
{
    /**
     * @var
     */
    private $identifier;
    /**
     * @var
     */
    private $time_from_previous;

    //Tracking Storage component
    /**
     * @var \modules\tracking\models\backend\TrackingStorage
     */
    public $tracking_storage;

    /**
     *
     */
    const EVENT_SETTINGS_STRUCT = [
        'EVENT_TYPE_ID',
        'EVENT_LOCATION',
        'EVENT_NAME',
        'EVENT_DESCRIPTION',
        'EVENT_TIME',
        'EVENT_IP',
        'EVENT_GROUPS',
        'EVENT_YII2_TRIGGERS',
    ];

    /**
     *
     */
    const EVENT_TYPES = [
        1 => [
            'eventName' => 'Unlock Ignite Item',
            'widgetTemplateName' => 'unlockItemEvent',
            'className' => 'modules\tracking\models\backend\TrackingUnlockItemEvent',
        ],
        2 => [
            'eventName' => 'Page Load',
            'widgetTemplateName' => 'pageLoadEvent',
            'className' => 'modules\tracking\models\backend\TrackingTablelessEvent',
        ],
        3 => [
            'eventName' => 'Change Event',
            'widgetTemplateName' => 'changeEvent',
            'className' => 'modules\tracking\models\backend\ChangeEvent',
        ],
        4 => [
            'eventName' => 'Error Event',
            'widgetTemplateName' => 'errorEvent',
            'className' => 'modules\tracking\models\backend\ErrorEvent',
        ],
        5 => [
            'eventName' => 'Warning Event',
            'widgetTemplateName' => 'warningEvent',
            'className' => 'modules\tracking\models\backend\WarningEvent',
        ],
        6 => [
            'eventName' => 'Time Range Event',
            'widgetTemplateName' => 'timeRangeEvent',
            'className' => 'modules\tracking\models\backend\TimeRangeEvent',
        ],
        7 => [
            'eventName' => 'Last Payment Event',
            'widgetTemplateName' => 'lastPaymentEvent',
            'className' => 'modules\tracking\models\backend\LastPaymentEvent',
        ],
    ];

    /**
     *
     */
    const TRACKING_STATES = [
        1 => 'NOT_READY_FOR_TRACKING',
        2 => 'READY_FOR_TRACKING',
    ];

    /**
     * TrackingSession constructor.
     */
    public function __construct()
    {
        $this->tracking_storage = new TrackingStorage();
        $this->continuePreviousSession();
        $this->setTrackingSessionIdentifier();
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%tracking_session}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['tracking_identifier', 'event_id', 'screen_url', 'event_name', 'event_description', 'event_ip'], 'string'],
            [['screen_id', 'event_type', 'event_subdomain_dealer_id'], 'integer'],
            [['event_time'], 'double'],
        ];
    }

    /**
     * @param $id
     *
     * @return array
     */
    public static function getTrackingView($id)
    {
        $view_data = [
            'lead' => null,
            'viewed_items_models' => null,
            'viewed_items_pages' => null,
            'unlocked_items_models' => null,
            'unlocked_items_pages' => null,
        ];

        $lead = IgniteLead::findOne($id);
        $searchModel1 = new UnlockedIgniteItemsSearch();
        $query = $searchModel1->search($lead->id);

        $countQuery = clone $query;
        $unlocked_items_pages = new Pagination(['totalCount' => $countQuery->count(), 'defaultPageSize' => 10]);
        $query->offset($unlocked_items_pages->offset)
            ->limit($unlocked_items_pages->limit);


        if (!empty($query->where['id'])) {
            $query->orderBy([new Expression('FIELD (id, ' . implode(',', $query->where['id']) . ')')]);
        }

        $unlocked_items_models = $query->all();

        $searchModel2 = new ViewedIgniteItemsSearch();
        $query = $searchModel2->search($lead->id);

        $countQuery = clone $query;
        $viewed_items_pages = new Pagination(['totalCount' => $countQuery->count(), 'defaultPageSize' => 20]);
        $query->offset($viewed_items_pages->offset)
            ->limit($viewed_items_pages->limit);

        if (!empty($query->where['id'])) {
            $query->orderBy([new Expression('FIELD (id, ' . implode(',', $query->where['id']) . ')')]);
        }

        $viewed_items_models = $query->all();

        $view_data = [
            'lead' => $lead,
            'viewed_items_models' => $viewed_items_models,
            'viewed_items_pages' => $viewed_items_pages,
            'unlocked_items_models' => $unlocked_items_models,
            'unlocked_items_pages' => $unlocked_items_pages,
        ];

        return $view_data;
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
     * @param $location_from_api
     *
     * @return bool|mixed
     */
    public function geolocate($location_from_api)
    {
        //If data not found in session provider - set it
        $tracking = $this->tracking_storage->getSessionProvider();

        if (empty($tracking['users_geolocation'])) {

            //Define location via google api
            //Even if api will not locate users location, system will have zip code by default 10001

            //First step is to get lng and latitude based on google geolocation api
            //$location_from_api = F3deshaHelpers::getCurrentLocationByGoogleGeolocationAPI();
            $location['users_geolocation']['api']['location'] = $location_from_api;

            //Second step is to get details about the place we found
            $details_about_geolocation = F3deshaHelpers::getGeolocationDetailsByGoogleGeolocationAPI($location_from_api['location']['lat'], $location_from_api['location']['lng']);
            $location['users_geolocation']['api']['location_details'] = $details_about_geolocation->results[0];
            //If location found, parse zip code into processed section
            if (!empty($location['users_geolocation']['api']['location_details'])) {
                $location['users_geolocation']['processed']['location_details'] = F3deshaHelpers::parseLocationObjectFromAPI($location['users_geolocation']['api']['location_details']);
            }

            $this->tracking_storage->setSessionProvider($location);

            return $location['users_geolocation']['processed']['location_details'];
        }

        return false;
    }

    /**
     * @param array $zipdata
     *
     * @return mixed
     */
    public function fillFakeGoogleApiLocationObject(array $zipdata)
    {
        $location['processed']['location_details']['city'] = $zipdata['city'];
        $location['processed']['location_details']['country'] = 'US';
        $location['processed']['location_details']['zip'] = $zipdata['zip'];
        $location['processed']['location_details']['state'] = $zipdata['state_code'];

        $location['api']['location']['location']['lat'] = $zipdata['latitude'];
        $location['api']['location']['location']['lng'] = $zipdata['longitude'];
        $location['api']['location_details'] = new \stdClass();

        $object1 = new \stdClass();
        $object1->types = ['postal_code'];
        $object1->short_name = $zipdata['zip'];
        $object1->long_name = $zipdata['zip'];

        $object2 = new \stdClass();
        $object2->types = ['locality'];
        $object2->long_name = $zipdata['city'];
        $object2->short_name = $zipdata['city'];

        $object3 = new \stdClass();
        $object3->types = ['administrative_area_level_1'];
        $object3->short_name = $zipdata['state_code'];

        $object4 = new \stdClass();
        $object4->types = ['country'];
        $object4->short_name = 'US';

        $location['api']['location_details']->address_components = [
            $object1,
            $object2,
            $object3,
            $object4,
        ];

        return $location;
    }

    /**
     * @param $zip
     *
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setGeolocationZipManual($zip)
    {

        $apiController = new DefaultController(1, 'dealerinventory');
        $isZipExists = $apiController->actionCheckZipExist($zip);
        if (!$isZipExists) {
            $zip = 10001;
        }

        $client = new Client();
        $response = $client->request('GET', "https://maps.googleapis.com/maps/api/geocode/json?address=$zip&sensor=true&key=AIzaSyCiJu37aYEL47ZdSfkHCZ-9LXWU-AaHQvI");
        $content = $response->getBody();
        $decode = json_decode($content);

        $location['api']['location_details'] = $decode->results[0];
        if (!empty($location['api']['location_details'])) {
            $location['processed']['location_details'] = F3deshaHelpers::parseLocationObjectFromAPI($location['api']['location_details']);
        }

        $this->tracking_storage->setSessionProvider(['users_geolocation' => $location]);

        return true;
    }

    /**
     * @param bool $previous_event
     *
     * @return int|void
     */
    public function assignDuration($previous_event = false)
    {
        //By default duration = 0, cause we possibly can have only one event
        $duration = 0;

        if ($this === $previous_event) {
            $this->time_from_previous = $duration;

            return;
        }

        if (!empty($previous_event)) {
            $this->time_from_previous = $previous_event->event_time - $this->event_time;
        }

        return $duration;
    }

    /**
     * @return mixed
     */
    public function getDurationInSeconds()
    {
        return $this->time_from_previous;
    }

    /**
     * @return string
     */
    public function getTimeFromPrevious()
    {
        return F3deshaHelpers::convertToDaysHrsMinsSeconds($this->time_from_previous);
    }

    /**
     * @return mixed|string
     */
    public static function getIp()
    {
        $ip = '8.8.8.8';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    /**
     * @param array $settings
     * @param array $override_params
     * @param array $optional_params
     */
    public function eventHandler(array $settings, array $override_params = [], array $optional_params = [])
    {
        try {
            foreach ($override_params as $key => $override_param) {
                if (in_array($key, self::EVENT_SETTINGS_STRUCT)) {
                    $settings[$key] = $override_param;
                } else {
                    throw new \ErrorException('Undefined param ' . $key);
                }
            }
            $this->registerEvent($settings, $optional_params);
        } catch (\ErrorException $e) {
            var_dump($e->getMessage());
            exit();
        }
    }

    /**
     * @param array $override_params
     * @param array $optional_params
     */
    public function registerIgniteItemUnlock(array $override_params = [], array $optional_params = [])
    {
        $settings = [
            'EVENT_TYPE_ID' => 1,
            'EVENT_LOCATION' => Yii::$app->request->absoluteUrl,
            'EVENT_NAME' => '',
            'EVENT_DESCRIPTION' => '',
            'EVENT_TIME' => F3deshaHelpers::getRequestTimeInNewYork(),
            'EVENT_IP' => self::getIp(),
        ];


        $this->eventHandler($settings, $override_params, $optional_params);
    }

    /**
     * @param array $override_params
     * @param array $optional_params
     */
    public function registerPageLoad(array $override_params = [], array $optional_params = [])
    {
        $settings = [
            'EVENT_TYPE_ID' => 2,
            'EVENT_LOCATION' => Yii::$app->request->absoluteUrl,
            'EVENT_NAME' => '',
            'EVENT_DESCRIPTION' => '',
            'EVENT_TIME' => F3deshaHelpers::getRequestTimeInNewYork(),
            'EVENT_IP' => self::getIp(),
        ];


        $this->eventHandler($settings, $override_params, $optional_params);
    }

    /**
     * @param array $override_params
     * @param array $optional_params
     */
    public function registerChangeEvent(array $override_params = [], array $optional_params = [])
    {
        $settings = [
            'EVENT_TYPE_ID' => 3,
            'EVENT_LOCATION' => Yii::$app->request->absoluteUrl,
            'EVENT_NAME' => '',
            'EVENT_DESCRIPTION' => '',
            'EVENT_TIME' => F3deshaHelpers::getRequestTimeInNewYork(),
            'EVENT_IP' => self::getIp(),
        ];


        $this->eventHandler($settings, $override_params, $optional_params);
    }

    /**
     * @param array $override_params
     * @param array $optional_params
     */
    public function registerErrorEvent(array $override_params = [], array $optional_params = [])
    {
        $settings = [
            'EVENT_TYPE_ID' => 4,
            'EVENT_LOCATION' => Yii::$app->request->absoluteUrl,
            'EVENT_NAME' => '',
            'EVENT_DESCRIPTION' => '',
            'EVENT_TIME' => F3deshaHelpers::getRequestTimeInNewYork(),
            'EVENT_IP' => self::getIp(),
        ];


        $this->eventHandler($settings, $override_params, $optional_params);
    }

    /**
     * @param array $override_params
     * @param array $optional_params
     */
    public function registerWarningEvent(array $override_params = [], array $optional_params = [])
    {
        $settings = [
            'EVENT_TYPE_ID' => 5,
            'EVENT_LOCATION' => Yii::$app->request->absoluteUrl,
            'EVENT_NAME' => '',
            'EVENT_DESCRIPTION' => '',
            'EVENT_TIME' => F3deshaHelpers::getRequestTimeInNewYork(),
            'EVENT_IP' => self::getIp(),
        ];


        $this->eventHandler($settings, $override_params, $optional_params);
    }

    /**
     * @param array $override_params
     * @param array $optional_params
     */
    public function registerTimeRangeEvent(array $override_params = [], array $optional_params = [])
    {
        $settings = [
            'EVENT_TYPE_ID' => 6,
            'EVENT_LOCATION' => Yii::$app->request->absoluteUrl,
            'EVENT_NAME' => '',
            'EVENT_DESCRIPTION' => '',
            'EVENT_TIME' => F3deshaHelpers::getRequestTimeInNewYork(),
            'EVENT_IP' => self::getIp(),
        ];


        //If $optional_params['event_action'] is open - get name string and add new var to tracking storage with current time in new york
        if (!empty($optional_params['event_action']) && $optional_params['event_action'] === 'open' && !empty($optional_params['event_string_id'])) {
            $this->tracking_storage->setSessionProvider(
                [
                    $optional_params['event_string_id'] =>
                        [
                            'Event Started' => date("Y-m-d H:i:s", F3deshaHelpers::getRequestTimeInNewYork()),
                        ],
                ]);
        }

        //If $optional_params['event_action'] is breakpoint - look for tracking storage name string array and add new value
        if (!empty($optional_params['event_action']) && $optional_params['event_action'] === 'breakpoint' && !empty($optional_params['event_string_id'])) {
            if (!empty($optional_params['event_data']) && is_array($optional_params['event_data'])) {
                foreach ($optional_params['event_data'] as $key => $val) {
                    $this->tracking_storage->setSessionProvider(
                        [
                            $optional_params['event_string_id'] =>
                                [
                                    $key => $val,
                                ],
                        ]);
                }
            }
        }

        //If $optional_params['event_action'] is close - look for tracking storage name string array and pull this to EVENT_DESCRIPTION data
        //and clear tracking storage name string array
        if (!empty($optional_params['event_action']) && $optional_params['event_action'] === 'close' && !empty($optional_params['event_string_id'])) {
            $this->tracking_storage->setSessionProvider(
                [
                    $optional_params['event_string_id'] =>
                        [
                            'Event Ended' => date("Y-m-d H:i:s", F3deshaHelpers::getRequestTimeInNewYork()),
                        ],
                ]);
            $override_params['EVENT_DESCRIPTION'] = $this->tracking_storage->getSessionProvider($optional_params['event_string_id']);
            //Clear this array from session storage
            $this->eventHandler($settings, $override_params, $optional_params);
            $this->tracking_storage->clearSessionProvider($optional_params['event_string_id']);
        }

    }

    /**
     * @param array $override_params
     * @param array $optional_params
     */
    public function registerLastPayment(array $override_params = [], array $optional_params = [])
    {
        $settings = [
            'EVENT_TYPE_ID' => 7,
            'EVENT_LOCATION' => Yii::$app->request->absoluteUrl,
            'EVENT_NAME' => '',
            'EVENT_DESCRIPTION' => '',
            'EVENT_TIME' => F3deshaHelpers::getRequestTimeInNewYork(),
            'EVENT_IP' => self::getIp(),
        ];

        $optional_params['updatableEvent'] = true;

        $this->eventHandler($settings, $override_params, $optional_params);
    }

    /**
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     *
     */
    public function continuePreviousSession()
    {
        //If no identifier in session, check on handler and if exists recreate session
        $session_provider = $this->tracking_storage->getSessionProvider();
        $cookie_provider = $this->tracking_storage->getCookieProvider();
        $handler = !empty($cookie_provider[TrackingStorage::HANDLER_KEY]) ? $cookie_provider[TrackingStorage::HANDLER_KEY] : false;

        if (empty($session_provider[TrackingStorage::IDENTIFIER_KEY]) && !empty($handler)) {
            $this->tracking_storage->setSessionProvider([TrackingStorage::IDENTIFIER_KEY => $handler]);
        }
    }

    /**
     * @param string $linking_string
     *
     * @return bool
     */
    public function setIdentifier(string $linking_string)
    {
        //Translate string into carvoy hash
        $hash = EncryptedDataProvider::getCarvoyHash($linking_string);

        //Set tracking storage session provider with this hash
        $this->tracking_storage->setSessionProvider([TrackingStorage::IDENTIFIER_KEY => $hash]);

        //Set tracking storage cookie provider with this hash
        $this->tracking_storage->setCookieProvider([TrackingStorage::HANDLER_KEY => $hash]);

        return $this->setTrackingSessionIdentifier();
    }

    /**
     * @return bool
     */
    private function setTrackingSessionIdentifier()
    {
        $session_provider = $this->tracking_storage->getSessionProvider();
        if (!empty($session_provider[TrackingStorage::IDENTIFIER_KEY])) {
            $this->identifier = $session_provider[TrackingStorage::IDENTIFIER_KEY];

            return true;
        }

        return false;
    }

    /**
     * @param \modules\tracking\interfaces\TrackingEventInterface $event
     *
     * @return int|mixed
     */
    public function getScreenId(TrackingEventInterface $event)
    {
        $filters = [
            'tracking_identifier' => $this->identifier,
        ];
        $session_found = TrackingSession::find()->where($filters)->orderBy('screen_id DESC')->one();
        if ($session_found) {
            return $event->getScreenIncrementing() ? $session_found->screen_id + 1 : $session_found->screen_id;
        } else {
            return 1;
        }
    }

    /**
     * @param array $event_params
     *
     * @throws \ErrorException
     */
    public function trackingEventValidation(array $event_params)
    {
        if (empty($event_params['EVENT_TYPE_ID'])) {
            throw new \ErrorException('No EVENT_TYPE_ID found in params');
        }
        if (empty($event_params['EVENT_LOCATION'])) {
            throw new \ErrorException('No EVENT_LOCATION found in params');
        }
        if (empty($event_params['EVENT_TIME'])) {
            throw new \ErrorException('No EVENT_TIME found in params');
        }
        if (empty($event_params['EVENT_IP'])) {
            throw new \ErrorException('No EVENT_IP found in params');
        }
    }

    /**
     * @param array $event_params
     * @param array $optional_params
     */
    public function registerEvent(array $event_params, array $optional_params = [])
    {
        if ($this->isReadyForTracking()) {
            try {
                $this->trackingEventValidation($event_params);

                if (array_key_exists($event_params['EVENT_TYPE_ID'], self::EVENT_TYPES)) {
                    $class = self::EVENT_TYPES[$event_params['EVENT_TYPE_ID']]['className'];

                    $null_or_id = null;
                    if (Yii::$app->domain->isActiveSubdomain) {
                        $null_or_id = Yii::$app->domain->dealershipManager->id;
                    }
                    if (!empty($optional_params['updatableEvent']) && $optional_params['updatableEvent'] === true && $event_already_exists = self::find()->where([
                            'tracking_identifier' => $this->identifier,
                            'event_name' => $event_params['EVENT_NAME'],
                            'event_type' => $event_params['EVENT_TYPE_ID'],
                            'event_subdomain_dealer_id' => $null_or_id,
                        ])->one()) {
                        //UpdatableEvent выполняет updateExistingEventStack если найдено соответствие Identifier и event_name
                        $event_link = $event_already_exists;
                        $event = $class::find()->where(['unique_event_id' => $event_already_exists->event_id])->one();

                        if (!empty($event_params['EVENT_DESCRIPTION'])) {
                            $event_link->event_description = serialize($event_params['EVENT_DESCRIPTION']);
                        }
                        $event_link->screen_url = $event_params['EVENT_LOCATION'];
                        $event_link->event_time = $event_params['EVENT_TIME'];
                        $event_link->event_ip = $event_params['EVENT_IP'];

                    } else {
                        //Updatable Event выполняет newEventStack если на найдено соответствия Identifier и event_name,
                        $event = new $class;
                        $event_link = new self();

                        //Create parent tracking_session and get its id
                        $event_link->tracking_identifier = $this->identifier;
                        $event_link->event_id = F3deshaHelpers::get_totally_unique_string();
                        $event_link->event_subdomain_dealer_id = $null_or_id;
                        $event_link->event_name = $event_params['EVENT_NAME'];
                        if (!empty($event_params['EVENT_DESCRIPTION'])) {
                            $event_link->event_description = serialize($event_params['EVENT_DESCRIPTION']);
                        }
                        $event_link->event_type = $event_params['EVENT_TYPE_ID'];
                        $event_link->screen_id = $this->getScreenId($event);

                        $event_link->screen_url = $event_params['EVENT_LOCATION'];
                        $event_link->event_time = $event_params['EVENT_TIME'];
                        $event_link->event_ip = $event_params['EVENT_IP'];
                    }


                    if ($event_link->validate()) {
                        if ($event_link->save()) {
                            $event_created = $event->registerEvent($event_link, $event_params, $optional_params);

                            $event_link->assignTrackingGroup($event_params);
                            $event_link->assignYii2Triggers($event_params);
                        }
                    }
                }

            } catch (\ErrorException $e) {
                var_dump($e->getMessage());
                exit();
            }

        }
    }

    /**
     * @param array $event_params
     */
    public function assignTrackingGroup(array $event_params)
    {
        if (!empty($event_params['EVENT_GROUPS'])) {
            foreach ($event_params['EVENT_GROUPS'] as $event_group) {
                $group_assignment = new $event_group['class'];
                $group_assignment->assign($this, $event_group['params']);
            }
        }
    }

    /**
     * @param array $event_params
     */
    public function assignYii2Triggers(array $event_params)
    {
        if (!empty($event_params['EVENT_YII2_TRIGGERS']) && is_array($event_params['EVENT_YII2_TRIGGERS'])) {
            foreach ($event_params['EVENT_YII2_TRIGGERS'] as $yii2_trigger_index => $yii2_trigger_content) {

                //Add according required data foreach event
                $tracking_lead = IgniteLead::find()->where(['tracking_identifier' => $this->tracking_identifier])->one();
                if (!empty($tracking_lead)) {
                    $yii2_trigger_content['trigger_params']['lead_id'] = $tracking_lead->id;
                }

                Yii::$app->trigger($yii2_trigger_content['trigger_name'], new Event(['sender' => $yii2_trigger_content['trigger_params']]));
            }
        }
    }

    /**
     * @param \modules\tracking\interfaces\TrackingEventInterface $event_instance
     *
     * @return int|string
     */
    public function getEventType(TrackingEventInterface $event_instance)
    {
        $class = get_class($event_instance);
        foreach (self::EVENT_TYPES as $i => $event_type) {
            if ($event_type['className'] === $class) {
                return $i;
            }
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLastPaymentEvent()
    {
        return $this->hasOne(LastPaymentEvent::className(), ['unique_event_id' => 'event_id']);
    }

    /**
     * @return bool
     */
    private function isReadyForTracking()
    {
        return !empty($this->identifier);
    }
}