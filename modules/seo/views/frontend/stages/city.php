<div class="new_find_catalog">


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
			<a href="#" class="btn_get_started state_page_btn">Get Started</a>
		</div>
		<div class="absolute_image">
			<img src="/statics/images/img/camaro.png" alt="camaro" />
			<img src="/statics/images/img/bmv.png" alt="bmv" />
			<img src="/statics/images/img/maserati.png" alt="maserati" />
		</div>
	</div>


	<div class="populars_cars_block">
		<div class="catalog_container">
			<h2>
				Popular new cars
			</h2>

			<?php
				echo \common\components\F3deshaHelpers::UniversalArrayLinkVisualizer($seocatalog->wrapInUALVDataProviderForCities());
				echo "<div style='text-align: center;'>";
				echo \yii\widgets\LinkPager::widget([
					'pagination' => $seocatalog->city_pages,
				]);
				echo "</div>";
			?>
		</div>
	</div>
</div>

<?php
	echo $this->render('@modules/seo/views/frontend/_outofstate_modal', [
			'image_url' => '/statics/images/img/camaro.png'
	])
?>
