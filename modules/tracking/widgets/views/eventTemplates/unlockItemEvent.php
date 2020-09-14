<?php
use modules\dealerinventory\models\backend\DealerinventoryPhotos;
?>
<li>
	<i class="fa fa-unlock bg-green"></i>

	<div class="timeline-item">
		<?php echo $this->render('eventHeader',['event'=>$event]);


		$lead = \modules\dealerinventory\models\backend\IgniteLead::find()->where(['tracking_identifier'=>$event->tracking_identifier])->one();
		//$unlock_item_link = \modules\tracking\models\backend\TrackingUnlockItemEvent::find()->where(['unique_event_id'=>$event->event_id])->one();
		$ignite_item = '';//\modules\dealerinventory\models\backend\Dealerinventory::find()->where(['id'=>$unlock_item_link->ignite_item_id])->one();

		//$photo_url = !empty($ignite_item->photos[0]->url) ? $ignite_item->photos[0]->url : '';
		//$img = DealerinventoryPhotos::findPhoto($photo_url, true);

		if(!empty($ignite_item)){ ?>
			<div class="timeline-footer" style="padding: 0px 35px 20px 35px;">
				<div class="box box-success box-solid collapsed-box">
					<div class="box-header with-border">
						<h3 class="box-title">Event Details</h3>

						<div class="box-tools pull-right">
							<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
							</button>
						</div>
						<!-- /.box-tools -->
					</div>
					<!-- /.box-header -->
					<div class="box-body" style="">


						<div class="row">
							<div class="col-sm-3">
								<p class="lead">Preview</p>
								<img class="img-responsive" style="border-radius: 10px" src="<?=$img?>" alt="Chromedata Preview">
							</div>
							<div class="col-sm-9">

								<div class="col-xs-6">
									<p class="lead"><?=$ignite_item->year.' '.$ignite_item->make.' '.$ignite_item->model.' '.$ignite_item->trim?></p>

									<div class="table-responsive">
										<table class="table">
											<tbody><tr>
												<th style="width:50%">Lead Name:</th>
												<td><?=$lead->name.' '.$lead->surname?></td>
											</tr>
											<tr>
												<th>Lead Email:</th>
												<td><?=$lead->email?></td>
											</tr>
											<tr>
												<th>Lead Phone:</th>
												<td><?=$lead->phone?></td>
											</tr>
											<tr>
												<th>Lead Zip:</th>
												<td><?=$lead->zip?></td>
											</tr>
											</tbody></table>
									</div>
								</div>


							</div>
						</div>


					</div>
					<!-- /.box-body -->
				</div>
			</div>
		<?php }
		?>
	</div>
</li>


