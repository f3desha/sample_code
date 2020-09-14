<?php

namespace modules\tracking\models\backend;

use common\components\dataproviders\EncryptedDataProvider;
use modules\seo\models\SeoCatalog;
use yii\db\ActiveRecord;

/**
 * Class TempStorage
 * We should have group of allowed tracking actions for running if tracking session ready for tracking
 *
 * @package modules\tracking\models\backend
 * @property string $id [varchar(255)]
 * @property string $class [varchar(255)]
 * @property string $value
 */
class TempStorage extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%temp_storage}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'value', 'class'], 'string'],
        ];
    }

    /**
     * @param string $key
     *
     * @return array|bool
     */
    public static function get(string $key)
    {
        $key = EncryptedDataProvider::getCarvoyHash($key);
        if ($storage_item = self::find()->where(['id' => $key])->limit(1)->all()) {
            $storage_item = $storage_item[0];

            return [
                'id' => $storage_item->id,
                'value' => unserialize($storage_item->value),
            ];
        }

        return false;
    }

    /**
     * @param string $key
     * @param $data
     * @param null $class
     *
     * @return array|bool
     */
    public static function set(string $key, $data, $class = null)
    {
        if (!$storage_item_array = self::get($key)) {
            $storage_item = new self();
        } else {
            $storage_item = self::find()->where(['id' => $storage_item_array['id']])->limit(1)->all();
            $storage_item = $storage_item[0];
        }
        $key = EncryptedDataProvider::getCarvoyHash($key);
        $storage_item->id = $key;
        $storage_item->value = serialize($data);
        if ($class) {
            $storage_item->class = $class;
        }
        if ($storage_item->validate()) {
            if ($storage_item->save()) {
                return [
                    'id' => $storage_item->id,
                    'value' => unserialize($storage_item->value),
                    'class' => $storage_item->class,
                ];
            }
        }

        return false;
    }

    /**
     * @param string $key
     *
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public static function clear(string $key)
    {
        if ($storage_item = self::get($key)) {
            $item = self::find()->where(['id' => $storage_item['id']])->one();
            $item->delete();

            return true;
        }

        return false;
    }

    /**
     *
     */
    public static function flushSeoCatalog()
    {
        self::deleteAll(['class' => SeoCatalog::TEMP_STORAGE_SEOCATALOG_CLASS]);
    }

    /**
     *
     */
    public static function flushAll()
    {
        self::deleteAll();
    }
}