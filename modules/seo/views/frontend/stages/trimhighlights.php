<div class="new_find_catalog model_page trimhighlights_page">
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
							<?=$seocatalog->config['year']?> <?=$seocatalog->config['make']?> <?=$seocatalog->config['model']?> <?=$seocatalog->config['trim']?>
						</strong>
						<p class="starting_cost">
							<span>Starting</span> <?=$seocatalog->getStartingMSRP()?> MSRP*
						</p>
						<?=$seocatalog->availableForCityWidget()?>
					</div>
				</div>
				<div class="right_column_top">
					<?=$seocatalog->buildYearWidget()?>
					<div class="wrap_img">
						<img src="<?php echo $seocatalog->getCarImageByMake($seocatalog->config['make']) ?>" title="<?=$seocatalog->config['make']?>" alt="<?=$seocatalog->config['make']?>" />
					</div>
				</div>
			</div>
			
		</div>
	</div>

	<div class="content_model">
		<div class="catalog_container">
			<h2>
				Highlights
			</h2>
			<div class="ankor_moder_block">
				<a href="interior_block" class="active">Interior and exterior</a>
			</div>
			<?=$seocatalog->buildTrimHighlightImageBlock()?>

			<div class="trimhighlights_tabs_block">
				<div class="tabs">
					<div class="tabs_list">
						<div class="tabs_item">
							Optional Equipment
						</div>
						<div class="tabs_item" data-slick='true'>
							Photo gallery
						</div>
						<div class="tabs_item">
							specifications
						</div>
					</div>
					<div class="tabs_content">
						<?=$seocatalog->buildOptionsBlock()?>
						<div class="content_descr">
							<?=$seocatalog->getTrimImagesGallery()?>
						</div>
						<?=$seocatalog->getFullCarSpecAccordion()?>
					</div>
				</div>
			</div>

			<div class="trimhighlights_accardion_mobile">
				<div class="trimhighlights_accardion_mobile_list">
					<?=$seocatalog->buildOptionsMobileBlock()?>
					<?=$seocatalog->getTrimImagesGalleryMobile()?>
					<?=$seocatalog->getFullCarSpecAccordionMobile()?>
				</div>
			</div>

		</div>
		<?php if(!$seocatalog->isInfoMode) { ?>
		<div class="model_similar_block">
			<div class="catalog_container">
				<h2>
					Popular new cars
				</h2>
				<?php echo \common\components\F3deshaHelpers::UniversalArrayLinkVisualizer($seocatalog->wrapInUALVDataProviderForModels()); ?>

			</div>
		</div>
		<div class="model_similar_block second_slider">
			<div class="catalog_container">
				<h2>
					<?=$seocatalog->config['make']?>'s in <?=$seocatalog->config['city']?>
				</h2>
				<?=$seocatalog->buildRandomIgniteItemsUALV()?>


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
