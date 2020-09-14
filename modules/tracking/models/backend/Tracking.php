<?php

namespace modules\tracking\models\backend;

use modules\dealerinventory\models\backend\Dealerinventory;
use modules\users\models\User;
use Yii;

/**
 * This is the model class for table "lfl_tracking".
 *
 * @property int $id
 * @property int $cookie_user_id
 * @property int $user_id
 * @property int $dealerinventory_id
 * @property int $page_id
 * @property int $lead_id
 * @property string $ip_address
 * @property string $link_from
 * @property string $link_to
 * @property string $car_link
 * @property double $duration
 * @property int $created_at
 * @property mixed $dealerinventory
 * @property mixed $user
 * @property int $updated_at
 */
class Tracking extends \yii\db\ActiveRecord
{

    /**
     *
     */
    const CREATE_COOKIE = 1;
    /**
     *
     */
    const ZERO = 0;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lfl_tracking';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'dealerinventory_id', 'page_id', 'lead_id', 'created_at', 'updated_at'], 'integer'],
//            [['link_from', 'link_to','cookie_user_id','car_link'], 'safe'],
            [['duration'], 'number'],
            [['ip_address'], 'string', 'max' => 255],
//	        [['link_from', 'cookie_user_id','link_to'], 'unique']
//	        [['link_from', 'created_at', 'cookie_user_id','link_to'], 'unique', 'targetAttribute' => ['link_from', 'created_at', 'cookie_user_id','link_to'], 'message' => 'exists']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'cookie_user_id' => Yii::t('app', 'Cookie User ID'),
            'user_id' => Yii::t('app', 'User'),
            'dealerinventory_id' => Yii::t('app', 'Dealerinventory ID'),
            'car_link' => Yii::t('app', 'Link'),
            'page_id' => Yii::t('app', 'Page ID'),
            'lead_id' => Yii::t('app', 'Lead ID'),
            'ip_address' => Yii::t('app', 'Ip Address'),
            'link_from' => Yii::t('app', 'Link'),
            'link_to' => Yii::t('app', 'Link To'),
            'duration' => Yii::t('app', 'Duration'),
            'created_at' => Yii::t('app', 'Date'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     *
     */
    public static function start(){

	}

    /**
     * @param $ids
     * @param null $scope
     *
     * @return array|\modules\tracking\models\backend\TempStorage[]|\modules\tracking\models\backend\Tracking[]|\yii\db\ActiveRecord[]
     */
    public static function findIdentities($ids, $scope = null)
    {
        $query = static::find()->where(['id' => $ids]);

        if ($scope !== null) {
            if (is_array($scope)) {
                foreach ($scope as $value) {
                    $query->$value();
                }
            } else {
                $query->$scope();
                $query->active();
            }
        }
        return $query->all();
    }

    /**
     * @param $id
     *
     * @return \modules\tracking\models\backend\Tracking|null
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDealerinventory()
    {
        return $this->hasOne(Dealerinventory::className(), ['id' => 'dealerinventory_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}