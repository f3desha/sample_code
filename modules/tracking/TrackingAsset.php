<?php

namespace modules\tracking;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Class TrackingAsset
 * @package modules\tracking
 */
class TrackingAsset extends AssetBundle
{
    /** @var string */
    public $sourcePath = '@modules/tracking/assets';

    /**
     * @var array
     */
	public $js = [
		//'js/frontend/dealerinventory.js'
	];
    /**
     * @var array
     */
	public $css = [
		'css/backend/tracking.css'
	];

    /** @var array */
   /* public $depends = [
        'modules\themes\site\HomeAsset',
		'backend\assets\AngularAsset'
    ];*/

	/*public $cssOptions = [
		'position' => View::POS_END,
	];*/

   /* public $jsOptions = [
        'position' => View::POS_END,
    ];*/

    /**
     * @inheritdoc
     */
//    public function init()
//    {
//        // $this->css[] = 'includes/royalslider/royalsli.css';
//        // $this->css[] = 'includes/royalslider/rs-defau.css';
//        // $this->js[] = 'includes/royalslider/jquery00.js';
//        // $this->js[] = 'includes/jquery.montage.js';
//        // $this->js[] = 'includes/readmore.js';
//        //$this->js[] = 'js/script.js';
//        //$this->css[] = 'css/owl.carousel.css';
//        $this->css[] = 'css/car-builder.css';
//        $this->js[] = 'js/carbuilder.js';
//        parent::init();
//    }
}
