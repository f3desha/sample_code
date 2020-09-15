<?php
use modules\seo\models\SeoCatalog;
/**
 * @var $seocatalog SeoCatalog
 */
?>

<div class="new_find_catalog make_page">
	<div class="top_bg">
		<div class="catalog_container">
			<div class="wrap_text">
				<div class="wrap_phone">
					<a href="tel:8666153273"><i class="fa fa-phone" aria-hidden="true"></i>866-615-3273</a>
				</div>
				<h2>
					The easiest way <br>to find next car
				</h2>
				<p>
					It’s simple - browse all makes and models, choose the car you want, fill out a short form, and we’ll do the rest
				</p>
			</div>
		</div>
		<div class="absolute_button">
			<a href="<?=$seocatalog->stage_based_ignite_link?>" class="btn_get_started">Get Started</a>
		</div>
		<div class="absolute_image">
			<img src="/statics/images/img/camaro.png" alt="camaro" />
			<img src="/statics/images/img/bmv.png" alt="bmv" />
			<img src="/statics/images/img/maserati.png" alt="maserati" />
		</div>
	</div>

	<div class="choose_brand_block">
		<div class="catalog_container">
			<h2>
				Choose your brand
			</h2>
			<?php echo \common\components\F3deshaHelpers::UniversalImageArrayLinkVisualizer($seocatalog->stage_data_provider['available_makes']); ?>
		</div>
	</div>
	<?php if(!$seocatalog->isInfoMode){ ?>
	<div class="populars_cars_block">
		<div class="catalog_container">
			<h2>
				Popular new cars
			</h2>
			<?php echo \common\components\F3deshaHelpers::UniversalArrayLinkVisualizer($seocatalog->wrapInUALVDataProviderForMakes()); ?>
		</div>
	</div>
	<?php } ?>
</div>

<?php
	echo $this->render('@modules/seo/views/frontend/_outofstate_modal', [
			'image_url' => '/statics/images/img/camaro.png'
	])
?>
