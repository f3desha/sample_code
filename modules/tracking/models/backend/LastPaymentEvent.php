<?php

namespace modules\tracking\models\backend;

use common\components\F3deshaHelpers;
use modules\tracking\interfaces\TrackingEventInterface;
use yii\db\ActiveRecord;

/**
 * Class LastPaymentEvent
 * We should have group of allowed tracking actions for running if tracking session ready for tracking
 *
 * @package modules\tracking\models\backend
 * @property int $id [int(11)]
 * @property string $unique_event_id [varchar(255)]
 * @property string $payment_type
 * @property float $msrp [double]
 * @property float $discount [double]
 * @property float $total_rebates [double]
 * @property int $term [int(11)]
 * @property int $miles_per_year [int(11)]
 * @property int $due_on_signing [int(11)]
 * @property float $monthly_price [double]
 * @property float $finance_discount [double]
 * @property int $finance_term [int(11)]
 * @property int $finance_due_on_signing [int(11)]
 * @property bool $screenIncrementing
 * @property mixed $trackingSession
 * @property float $finance_monthly_price [double]
 */
class LastPaymentEvent extends ActiveRecord implements TrackingEventInterface
{

    /**
     * @var bool
     */
    private $screen_incrementing = false;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%tracking_last_payment_event}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['unique_event_id', 'payment_type'], 'string'],
            [
                [
                    'monthly_price',
                    'finance_monthly_price',
                    'msrp',
                    'discount',
                    'total_rebates',
                    'finance_discount',
                ],
                'double',
            ],
            [
                [
                    'due_on_signing'
                    ,
                    'term',
                    'miles_per_year',
                    'finance_term',
                    'finance_due_on_signing',
                ],
                'integer',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return [
            'lease_payment' => [
                'unique_event_id',
                'payment_type',
                'discount',
                'total_rebates',
                'term',
                'miles_per_year',
                'due_on_signing',
                'monthly_price',
            ],
            'finance_payment' => [
                'unique_event_id',
                'payment_type',
                'finance_discount',
                'finance_term',
                'finance_monthly_price',
                'finance_due_on_signing',
                'finance_monthly_price',
            ],
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
     * @param int $current_payment_type
     */
    public function buildPaymentTypeOrder(int $current_payment_type)
    {
        //Payment type always has only unique types, and current type unsets from array and sets to the end
        $payment_types = [];
        if (!empty($this->payment_type)) {
            $payment_types = explode(',', $this->payment_type);
            $type_already_in_array_position = array_search((string)$current_payment_type, $payment_types);
            if ($type_already_in_array_position !== false) {
                unset($payment_types[$type_already_in_array_position]);
            }
        }
        $payment_types[] = (string)$current_payment_type;

        $this->payment_type = implode(',', $payment_types);
    }

    /**
     * @param array $optional_params
     */
    public function savePaymentDetails(array $optional_params)
    {
        if (!empty($optional_params['payment_type'])) {
            switch ($optional_params['payment_type']) {
                case 1:
                    //Lease payment type
                    $this->setScenario('lease_payment');

                    $this->buildPaymentTypeOrder((int)$optional_params['payment_type']);
                    $this->msrp = $optional_params['msrp'];
                    $this->discount = $optional_params['discount'];
                    $this->total_rebates = $optional_params['total_rebates'];
                    $this->term = (int)$optional_params['term'];
                    $optional_params['miles_per_year'] = str_replace(' ', '', $optional_params['miles_per_year']);
                    $this->miles_per_year = (int)$optional_params['miles_per_year'];
                    $this->due_on_signing = $optional_params['due_on_signing'];
                    $this->monthly_price = $optional_params['monthly_price'];
                    break;
                case 2:
                    //Finance payment type
                    $this->setScenario('finance_payment');
                    $this->buildPaymentTypeOrder((int)$optional_params['payment_type']);
                    $this->msrp = $optional_params['msrp'];
                    $this->finance_discount = $optional_params['discount'];
                    $this->finance_term = (int)$optional_params['term'];
                    $this->finance_due_on_signing = $optional_params['due_on_signing'];
                    $this->finance_monthly_price = $optional_params['monthly_price'];
                    break;
            }
        }
    }

    /**
     * @param \modules\tracking\models\backend\TrackingSession $trackingSession
     * @param array $event_params
     * @param array $optional_params
     */
    public function registerEvent(TrackingSession $trackingSession, array $event_params, array $optional_params = [])
    {
        try {
            $this->unique_event_id = $trackingSession->event_id;
            $this->savePaymentDetails($optional_params);
            if ($this->validate()) {
                $this->save();
            } else {
                $this->getErrors();
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