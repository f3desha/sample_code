<?php

use modules\seo\helpers\Meta;
use modules\seo\models\SeoCatalog;
use modules\seo\SeoCatalogAsset;
use modules\themes\site\InfoLandingPagesAsset;

/* @var $this yii\web\View */
/* @var $seocatalog SeoCatalog */

InfoLandingPagesAsset::register($this);
SeoCatalogAsset::register($this);
$this->registerJsFile(
    'https://www.google.com/recaptcha/api.js?hl=en',
    [
        'async' => true,
        'defer' => true,
    ]
);
$this->title = Meta::all('seocatalog', $seocatalog);