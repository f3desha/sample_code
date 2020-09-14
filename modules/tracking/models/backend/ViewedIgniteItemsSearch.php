<?php

namespace modules\tracking\models\backend;

use modules\dealerinventory\models\backend\Dealerinventory;
use modules\dealerinventory\models\backend\IgniteLead;
use modules\users\models\backend\User;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

/**
 * Class ViewedIgniteItemsSearch
 * @package modules\tracking\models\backend
 */
class ViewedIgniteItemsSearch extends TrackingSession
{
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
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * @param $lead_id
     *
     * @return \yii\db\ActiveQuery
     */
    public function search($lead_id)
    {


        $lead = IgniteLead::find()->select('tracking_identifier')->where(['id' => $lead_id])->one();
        $query = DealerinventoryHasEvent::find()
            ->select('lfl_tracking_dealerinventory_has_events.ignite_item_id')
            ->leftJoin('lfl_tracking_session', '`lfl_tracking_dealerinventory_has_events`.`event_id` = `lfl_tracking_session`.`event_id`')
            ->where(['lfl_tracking_dealerinventory_has_events.tracking_identifier' => $lead->tracking_identifier])
            ->andWhere(['!=', 'lfl_tracking_session.event_type', 1])
            ->with('event')->orderBy([
                'lfl_tracking_session.event_time' => SORT_ASC,
            ])->distinct();
        if (Yii::$app->domain->isDealershipSubdomain) {
            $ids = (ArrayHelper::getColumn(User::find()->where(['role' => 'dealer-manager', 'dealer_id' => Yii::$app->domain->dealership->id])->all(), 'id'));

            $query->andWhere(['IN', 'lfl_tracking_session.event_subdomain_dealer_id', $ids]);
        }
        $distinct_ignite_items_ids = $query->all();
        $ignite_ids = array_column($distinct_ignite_items_ids, 'ignite_item_id');
        $ignite_ids = array_reverse($ignite_ids, true);

        //Get active query of dealerinventory for pagination with items ids

        $query = Dealerinventory::find()->where(['id' => $ignite_ids]);

        return $query;

    }
}