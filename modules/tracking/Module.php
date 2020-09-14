<?php

namespace modules\tracking;

/**
 * Api module definition class
 */
class Module extends \yii\base\Module
{
    public $controllerNamespace = 'modules\tracking\controllers\frontend';

    public $isBackend = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->isBackend === true) {
            $this->setViewPath('@modules/tracking/views/backend');
        } else {
            $this->layout = 'main';
            $this->setViewPath('@modules/tracking/views/frontend');
        }
    }


    /**
     * Check if module is used for backend application.
     * @return boolean true if it's used for backend application.
     */
    public function getIsBackend()
    {
        return $this->isBackend;
    }

    public static function t($category, $message, $params = [], $language = null)
    {
        return \Yii::t('modules/' . $category, $message, $params, $language);
    }
}
