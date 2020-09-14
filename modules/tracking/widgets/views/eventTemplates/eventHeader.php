<span class="time"><i class="fa fa-clock-o"></i> <?php echo date("Y-m-d H:i:s", $event->event_time); ?> | <?=$event->getTimeFromPrevious()?></span>

<h3 class="timeline-header">Event Location: <a href="<?=$event->screen_url?>"><?=$event->screen_url?></a> </h3>

<div class="timeline-body">
	<section class="invoice">
		<!-- title row -->
		<div class="row">
			<div class="col-xs-12">
				<h2 class="page-header">
					</i> Event Type: <?=\modules\tracking\widgets\TrackingSessionTimeline::getEventName($event)?>
					<small class="pull-right"></small>
				</h2>
			</div>
			<!-- /.col -->
		</div>
		<!-- info row -->
		<div class="row invoice-info">
			<div class="col-sm-4 invoice-col">
				<b>Event Id:</b> <?=$event->event_id?><br>
				<b>IP Address:</b> <?=$event->event_ip?><br>
				<?php if(!empty($event->event_description)){
					$unserialized = unserialize($event->event_description);
					foreach ($unserialized as $key => $additional_data){
						echo "<b>{$key}: </b>{$additional_data}<br>";
					}
				} ?>
			</div>
			<!-- /.col -->
			<?php if(!empty($event->lastPaymentEvent)){
				//Get all types
				if(!empty($event->lastPaymentEvent->payment_type)){
					$all_viewed_types = explode(',',$event->lastPaymentEvent->payment_type);
					$last_viewed_payment_type = $all_viewed_types[count($all_viewed_types)-1];
					$rest_viewed_types = $all_viewed_types;
					unset($rest_viewed_types[count($all_viewed_types)-1]);

					echo \modules\tracking\widgets\TrackingSessionTimeline::getLastPaymentDataByType((int)$last_viewed_payment_type, $event);
				}

				?>
			<?php } ?>
<!--			Get Last Payment viewed-->
<!--			Get Rest of the payments viewed-->
		</div>

		<?php
			if(!empty($rest_viewed_types)){
				foreach ($rest_viewed_types as $type){ ?>
					<!-- title row -->
					<div class="row">
						<div class="col-xs-12">
							<h2 class="page-header">
								<?php
									if((int)$type === 1){
										$type_name = "Lease";
									} elseif((int)$type === 2){
										$type_name = "Finance";
									}
								?>
								</i> Also viewed <?=$type_name?>
								<small class="pull-right"></small>
							</h2>
						</div>
						<!-- /.col -->
					</div>
					<!-- info row -->
					<div class="row invoice-info">
						<!-- /.col -->
						<?php
							//Get all types
							echo "<div class=\"col-sm-4 invoice-col\"></div>";
							echo \modules\tracking\widgets\TrackingSessionTimeline::getLastPaymentDataByType((int)$type, $event);


							?>
						<?php  ?>
						<!--			Get Last Payment viewed-->
						<!--			Get Rest of the payments viewed-->
					</div>
				<?php }
			}
		?>
		<!-- /.row -->
	</section>
</div>