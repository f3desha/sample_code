<?php

namespace modules\tracking\models\backend;

use modules\dealerinventory\models\backend\IgniteLead;
use modules\users\models\backend\User;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use modules\tracking\models\backend\Tracking;

/**
 * Class TrackingSessionSearch
 * @package modules\tracking\models\backend
 * TrackingSearch represents the model behind the search form of `modules\tracking\models\backend\Tracking`.
 */
class TrackingSessionSearch extends TrackingSession
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
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
       $lead_id = Yii::$app->getRequest()->getQueryParam('id');


		$lead = IgniteLead::find()->select('tracking_identifier')->where(['id' => $lead_id])->one();
		$query = TrackingSession::find();

		// изменяем запрос добавляя в его фильтрацию

		$query->andFilterWhere(['tracking_identifier' => $lead->tracking_identifier])->orderBy([
			'event_time' => SORT_DESC
		]);
		//	->andFilterWhere(['like', 'creation_date', $this->creation_date]);

		return $query;

    }
}