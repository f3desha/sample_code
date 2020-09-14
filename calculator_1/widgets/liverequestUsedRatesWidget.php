<?php namespace common\components\calculator\widgets;

use common\components\calculator\widgets\interfaces\CalculatorWidget;
use yii\base\Widget;

/**
 * Class liverequestUsedRatesWidget
 * @package common\components\calculator\widgets
 */
class liverequestUsedRatesWidget extends Widget
{

    /**
     * @return string
     */
    public function run()
    {

		return $this->render('liverequestusedrates');
    }
}