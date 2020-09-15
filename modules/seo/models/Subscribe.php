<?php

namespace modules\seo\models;

use yii\db\ActiveRecord;

/**
 * Class Route
 * @package modules\seo\models
 * Saved urls
 * @property int $id ID
 * @property string $url Url
 * @property string $route Route
 * @property int $created_at Created at
 * @property int $updated_at Updated at
 * @property int $route_id [int(11)]
 * @property string $email [varchar(320)]
 * @property string $params [varchar(4096)]
 */
class Subscribe extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{%subscribe}}';
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [

        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [

            ['email', 'string', 'max' => 320],
            [['email', 'route_id'], 'required']

        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [

        ];
    }


}