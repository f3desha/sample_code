<?php

namespace modules\tracking\models\backend;

use modules\dealerinventory\models\backend\IgniteLead;
use modules\users\models\backend\Dealership;
use modules\users\models\backend\DealershipGroup;
use modules\users\models\backend\User;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use modules\tracking\models\backend\Tracking;

/**
 * Class TrackingSearch
 * @package modules\tracking\models\backend
 * TrackingSearch represents the model behind the search form of `modules\tracking\models\backend\Tracking`.
 */
class TrackingSearch extends Tracking
{
    /**
     * @var
     */
    public $year;
    /**
     * @var
     */
    public $make;
    /**
     * @var
     */
    public $model;
    /**
     * @var
     */
    public $trim;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'dealerinventory_id', 'lead_id', 'updated_at'], 'integer'],
            [['cookie_user_id', 'page_id', 'ip_address', 'link_to', 'year', 'make', 'model', 'trim', 'created_at', 'car_link'], 'safe'],
            [['duration'], 'number'],
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
        $lead_id = !empty($params['id']) ? $params['id'] : Yii::$app->getRequest()->getQueryParam('id');
        $lead = IgniteLead::findOne($lead_id);

        //die('hello');
        $current_user = User::findOne(Yii::$app->user->identity->id);

        $leads = IgniteLead::find()->where(["email" => $lead->email])->all();
        $cookies_ids = null;
        foreach ($leads as $l) {
            if (!empty($l->cookie_user_id)) {
                $cookies_ids[$l->cookie_user_id] = $l->cookie_user_id;
            }
        }

        $query = Tracking::find()->joinWith('dealerinventory')->joinWith('user.dealership')->where(["{{%tracking}}.cookie_user_id" => $cookies_ids]);


        /*if($current_user->isRole('moderator') || $current_user->isRole('admin')) {
            $query->innerJoin('{{%dealerinventory}}', 'dealerinventory_id={{%dealerinventory}}.id');
        }else if($current_user->isRole('dealer')) {
            $query->innerJoin('{{%dealerinventory}}', 'dealerinventory_id={{%dealerinventory}}.id')->andWhere(["{{%dealerinventory}}.user_id" => Yii::$app->user->identity->id]);
        }
        */

        $user = User::findOne(Yii::$app->user->id);

        if ($user->hasRole('superadmin')) {

        } else {
            if ($user->hasRole('dealer-moderator')) {

                $hasSubdomainDealers = User::find()->where(['role' => 'dealer'])->orWhere(['role' => 'dealer-manager'])->andWhere(['<>', 'username', ''])->all();

                $ids = [];

                foreach ($hasSubdomainDealers as $dealer) {
                    $ids[] = $dealer->id;
                }
                $query->andWhere([
                    'IN',
                    Tracking::tableName() . '.user_id',
                    $ids,
                ]);

            } else {
                if ($user->hasRole('dealer-group-manager')) {

                    $group = DealershipGroup::findOne($user->dealer_id)->group;

                    $ids = [];
                    foreach ($group as $dealership) {
                        $ids[] = $dealership->id;
                    }

                    $hasDealers = User::find()->where(['role' => 'dealer'])->orWhere(['role' => 'dealer-manager'])
                        ->andWhere(['<>', 'username', ''])
                        ->andWhere(['IN', 'dealer_id', $ids])->all();

                    $ids2 = [];
                    foreach ($hasDealers as $dealer) {
                        $ids2[] = $dealer->id;
                    }


                    $query->andWhere([
                        'IN',
                        Tracking::tableName() . '.user_id',
                        $ids2,
                    ]);

                } else {
                    if ($user->hasRole('dealer') || $user->hasRole('dealer-manager')) {

                        $hasDealers = User::find()->where(['role' => 'dealer'])->orWhere(['role' => 'dealer-manager'])
                            ->andWhere(['<>', 'username', ''])
                            ->andWhere(['dealer_id' => $user->dealer_id])->all();

                        $ids = [];

                        foreach ($hasDealers as $dealer) {
                            $ids[] = $dealer->id;
                        }
                        $query->andWhere([
                            'IN',
                            Tracking::tableName() . '.user_id',
                            $ids,
                        ]);

                    } else {
                        if ($user->hasRole('marketplace-manager')) {

                            $query->andWhere([
                                '=',
                                Dealership::tableName() . '.show_cars_in_ignite_catalog',
                                '1',
                            ]);

                        } else {
                            if ($user->hasRole('marketplace-client-manager')) {

                                $query->andWhere([
                                    '=',
                                    Dealership::tableName() . '.show_cars_in_ignite_catalog',
                                    '1',
                                ]);

                            }
                        }
                    }
                }
            }
        }


        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);


        $dataProvider->sort->attributes['dealerinventory'] = [
            'asc' => ['dealerinventory.year' => SORT_ASC],
            'desc' => ['dealerinventory.year' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['dealerinventory'] = [
            'asc' => ['dealerinventory.make' => SORT_ASC],
            'desc' => ['dealerinventory.make' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['dealerinventory'] = [
            'asc' => ['dealerinventory.model' => SORT_ASC],
            'desc' => ['dealerinventory.model' => SORT_DESC],
        ];
        $dataProvider->sort->attributes['dealerinventory'] = [
            'asc' => ['dealerinventory.trim' => SORT_ASC],
            'desc' => ['dealerinventory.trim' => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            '{{%tracking}}.id' => $this->id,
            'user_id' => $this->user_id,
            'dealerinventory_id' => $this->dealerinventory_id,
            'lead_id' => $this->lead_id,
            'duration' => $this->duration,
            '{{%tracking}}.created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'cookie_user_id', $this->cookie_user_id])
            ->andFilterWhere(['like', 'page_id', $this->page_id])
            ->andFilterWhere(['like', 'ip_address', $this->ip_address])
            ->andFilterWhere(['like', 'link_to', $this->link_to])
            ->andFilterWhere(['like', 'year', $this->year])
            ->andFilterWhere(['like', 'make', $this->make])
            ->andFilterWhere(['like', 'model', $this->model])
            ->andFilterWhere(['like', 'trim', $this->trim]);

        return $dataProvider;
    }
}
