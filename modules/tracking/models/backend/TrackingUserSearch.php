<?php

namespace modules\tracking\models\backend;

use modules\users\models\backend\User;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Class TrackingUserSearch
 * @package modules\tracking\models\backend
 * TrackingSearch represents the model behind the search form of `modules\tracking\models\backend\Tracking`.
 */
class TrackingUserSearch extends Tracking
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
     * @param $params
     *
     * @return \yii\data\ActiveDataProvider
     */
    public function search($params)
    {
        $user_id = Yii::$app->getRequest()->getQueryParam('id');
        $tracked_user = User::findOne($user_id);
        $current_user = User::findOne(Yii::$app->user->identity->id);


        $query = Tracking::find()->where(["{{%tracking}}.cookie_user_id" => $tracked_user->cookie_user_id]);

        if ($current_user->isRole('moderator') || $current_user->isRole('admin')) {

            $query->innerJoin('{{%dealerinventory}}', 'dealerinventory_id={{%dealerinventory}}.id');

        } else {
            if ($current_user->isRole('dealer')) {

                $query->innerJoin('{{%dealerinventory}}', 'dealerinventory_id={{%dealerinventory}}.id')
                    ->where(["{{%dealerinventory}}.user_id" => Yii::$app->user->identity->id]);

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
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
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
            //  ->andFilterWhere(['like', 'link_from', $this->link_from])
            ->andFilterWhere(['like', 'link_to', $this->link_to])
            ->andFilterWhere(['like', 'year', $this->year])
            ->andFilterWhere(['like', 'make', $this->make])
            ->andFilterWhere(['like', 'model', $this->model])
            // ->andFilterWhere(['like', 'car_link', $this->model])
            ->andFilterWhere(['like', 'trim', $this->trim]);

        return $dataProvider;
    }
}
