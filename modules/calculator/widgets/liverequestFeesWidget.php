<?php namespace common\components\calculator\widgets;

use yii\base\Widget;

/**
 * Class liverequestFeesWidget
 * @package common\components\calculator\widgets
 */
class liverequestFeesWidget extends Widget
{

    /**
     * @return string
     */
    public function run()
    {

		return $this->render('liverequestfees');
    }
}