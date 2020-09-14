<?php

namespace common\components\calculator\widgets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Class CalculatorWidgetAsset
 * @package common\components\calculator\widgets
 */
class CalculatorWidgetAsset extends AssetBundle
{
    /** @var string */
    public $sourcePath = '@common/components/calculator/widgets/assets';

    /**
     * @var array
     */
    public $js = [
		'angular/app.js'
	];
    /**
     * @var array
     */
    public $css = [
		'css/calculator.css'
	];

    /** @var array */
    public $depends = [

    ];

    /**
     * @var array
     */
    public $cssOptions = [
		//'position' => View::POS_END,
	];

    /**
     * @var array
     */
    public $jsOptions = [
        //'position' => View::POS_END,
    ];

}
