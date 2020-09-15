<?php
use modules\seo\models\SeoCatalog;
use modules\seo\models\SeoLocatorForm;
use modules\site\models\ContactForm;

/**
 * @var $seocatalog SeoCatalog
 * @var $ziplocator SeoLocatorForm
 * @var $contact ContactForm
 */
?>
<?php echo $this->render('@modules/seo/views/layouts/head', [
		'seocatalog' => $seocatalog
]) ?>
<?php echo $this->render('//layouts/header', []) ?>

<div class="mainWrapper homepage" style="margin-top: 59px" ng-controller="SeocatalogController" ng-cloak>
<!--<?php echo modules\themes\site\widgets\Alert::widget(); ?>-->
	<?=$this->render('@modules/seo/views/frontend/stages/'.$seocatalog->stage_view, [
		'seocatalog' => $seocatalog,
		'ziplocator' => $ziplocator
	]); ?>
<?php

	?>

	<?php echo $this->render('@modules/seo/views/layouts/contact', ['contact' => $contact]) ?>
</div>
