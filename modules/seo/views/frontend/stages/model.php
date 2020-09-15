<div class="new_find_catalog brand_page model_page make_page">
	<div class="top_bg">
		<div class="catalog_container">
			<div class="row_flex">
				<div class="left_column_top">
					<div class="descr_top_block make_descr">
						<?php
						echo $seocatalog->breadcrumbsWidget();
						?>
						<div class="wrap_phone">
							<a href="tel:8666153273"><i class="fa fa-phone" aria-hidden="true"></i>866-615-3273</a>
						</div>
						<div class="logo_model">
							<img src="<?php echo $seocatalog->getLogoByMake($seocatalog->config['make']) ?>" alt="<?=$seocatalog->config['make']?>" />
						</div>
						<h2>
							Lease your next <?=$seocatalog->config['make']?>
						</h2>
						<p>
							<?=$seocatalog->config['make']?> models commonly include Driver Assistance, Keyless Entry, Bluetooth Audio,<br> Steering Wheel Controls, Keyless Ignition,<br> Blind Spot Monitoring, Leather Steering Wheel,<br> Exterior Cameras, Rear Backup Camera, and Spoiler
						</p>
						<div class="wrap_btn">
							<a href="" class="btn_explore">Explore</a>
						</div>
					</div>
				</div>
				<div class="right_column_top">
					<div class="wrap_img">
						<img src="<?php echo $seocatalog->getCarImageByMake($seocatalog->config['make']) ?>" title="<?=$seocatalog->config['make']?>" alt="<?=$seocatalog->config['make']?>" />
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="content_model">
		<div class="model_similar_block">
			<div class="catalog_container">
				<h2>
					Available <?=$seocatalog->config['make']?> Models
				</h2>
				<?php echo $seocatalog->buildModelSlider(); ?>
			</div>
		</div>
		<?php if(!$seocatalog->isInfoMode){ ?>
		<div class="populars_cars_block">
			<div class="catalog_container">
				<h2>
					Popular new cars
				</h2>
				<?php echo \common\components\F3deshaHelpers::UniversalArrayLinkVisualizer($seocatalog->wrapInUALVDataProviderForModels()); ?>

			</div>
		</div>
		<?php } ?>
	</div>
	<?php if(!$seocatalog->isInfoMode){ ?>
	<div class="content_model">
		<div class="model_similar_block">
			<div class="catalog_container">
				<h2>
					<?=$seocatalog->config['make']?>'s in <?=$seocatalog->config['city']?>
				</h2>
				<?=$seocatalog->buildRandomIgniteItemsUALV()?>
			</div>
		</div>
	</div>
	<?php } ?>
</div>

<?php
	echo $this->render('@modules/seo/views/frontend/_outofstate_modal', [
			'image_url' => $seocatalog->getCarImageByMake($seocatalog->config['make'])
	])
?>
