<?php

namespace modules\seo;

use yii\web\AssetBundle;

/**
 * Info Landing Pages asset bundle.
 */
class SeoCatalogAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = '@modules/seo/assets';

    /**
     * @var array
     */
    public $css = [
        'css/seocatalog.scss'
    ];

    /**
     * @var array
     */
    public $js = [
        'js/seoscript.js',
    ];
}
