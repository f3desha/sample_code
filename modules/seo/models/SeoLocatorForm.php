<?php

namespace modules\seo\models;

use yii\base\Model;

/**
 * Class SeoLocatorForm
 * @package modules\seo\models
 */
class SeoLocatorForm extends Model
{
    /**
     * @var
     */
    public $zip;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['zip', 'string'],
        ];
    }

}