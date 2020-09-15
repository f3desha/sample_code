<div class="new_find_catalog model_page">
	<div class="top_bg">
		<div class="catalog_container">
			<div class="row_flex">
				<div class="left_column_top" ng-controller="HeaderController">
					<div class="descr_top_block">
						<?php
							echo $seocatalog->breadcrumbsWidget();
						?>
						<div class="wrap_phone">
							<a href="tel:8666153273"><i class="fa fa-phone" aria-hidden="true"></i>866-615-3273</a>
						</div>
						<div class="logo_model">
							<img src="<?php echo $seocatalog->getLogoByMake($seocatalog->config['make']) ?>" alt="<?=$seocatalog->config['make']?>" />
						</div>
						<strong class="title_model">
							<?=$seocatalog->config['year']?> <?=$seocatalog->config['make']?> <?=$seocatalog->config['model']?>
						</strong>
						<p class="starting_cost">
							<span>Starting</span> <?php
							echo $seocatalog->getStartingMSRP();
							?> MSRP*
						</p>
						<?=$seocatalog->availableForCityWidget()?>
					
					</div>
				</div>
				<div class="right_column_top">
					<?=$seocatalog->buildYearWidget()?>
					<div class="wrap_img">
						<img src="<?php 
						echo $seocatalog->getCarImageByMake($seocatalog->config['make']);
						?>" title="<?=$seocatalog->config['make']?>" alt="<?=$seocatalog->config['make']?>" />
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="content_model">
		<div class="catalog_container">
			<div class="ankor_moder_block" id="model_car_wrapper">
				<a href="model_car_wrapper" class="active">Trim comparison</a>
				<a href="model_car_gallery">Photo Gallery</a>
			</div>


			<h2>
				<?=$seocatalog->config['year']?> <?=$seocatalog->config['make']?> <?=$seocatalog->config['model']?> Trim Comparison
			</h2>

			<div class="model_car_wrapper">
				<div class="left_car_column">
					<div class="model_car_info_list">
						<div class="model_car_info_item">
							<p>
								Standard
							</p>
						</div>
						<div class="model_car_info_item">
							<p>
								Optional
							</p>
						</div>
					</div>
					<p>
						* Additional options<br> available
					</p>
				</div>
				<div class="right_car_column">
					<?php echo $seocatalog->buildTrimSlider(); ?>
				</div>
			</div>


			<?php echo $seocatalog->buildTrimCompareTable(); ?>
		</div>
		<div class="model_car_gallery" id="model_car_gallery">
			<div class="catalog_container">
				<h2>
					Photo Gallery
				</h2>
				<?=$seocatalog->getTrimGroupGallery()?>
			</div>
		</div>
		<?php if(!$seocatalog->isInfoMode){ ?>
		<div class="populars_cars_block">
			<div class="catalog_container">
				<h2>
					Popular new cars
				</h2>
				<?php 
				echo \common\components\F3deshaHelpers::UniversalArrayLinkVisualizer($seocatalog->wrapInUALVDataProviderForModels()); 
				?>

			</div>
		</div>
		<div class="model_similar_block">
			<div class="catalog_container">
				<h2>
					<?=$seocatalog->config['make']?>'s in <?=$seocatalog->config['city']?>
				</h2>
				<?php echo $seocatalog->buildRandomIgniteItemsUALV(); ?>

			</div>
		</div>
		<?php } ?>
	</div>
</div>

<?php
	echo $this->render('@modules/seo/views/frontend/_outofstate_modal', [
			'image_url' => $seocatalog->getCarImageByMake($seocatalog->config['make'])
	])
?>
