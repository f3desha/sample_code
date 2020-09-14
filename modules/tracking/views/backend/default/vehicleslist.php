<?php


?>
<div class="row">
	<div class="col-xs-12 table-responsive">
		<table class="table table-striped">
			<thead>
			<tr>
				<th>Year</th>
				<th>Make</th>
				<th>Model</th>
				<th>Trim</th>
				<th>Monthly</th>
				<th>Out of pocket</th>
				<th>IP Adress</th>
				<th>Date</th>
				<th>Time</th>
				<th>Duration</th>
				<th></th>
			</tr>
			</thead>
			<tbody>
			<?php
				foreach ($items_models as $viewed_item){
					$last_payment_event = \modules\tracking\models\backend\DealerinventoryHasEvent::find()
						->leftJoin('lfl_tracking_session', '`lfl_tracking_dealerinventory_has_events`.`event_id` = `lfl_tracking_session`.`event_id`')
						->where(['lfl_tracking_dealerinventory_has_events.tracking_identifier' => $lead->tracking_identifier, 'ignite_item_id'=>$viewed_item->id])
						->andWhere(['=', 'lfl_tracking_session.event_type', 7])
						->with('event')->all();

					$all_items_events_ids = \modules\tracking\models\backend\DealerinventoryHasEvent::find()
						->select('event_id')->where(['tracking_identifier'=>$lead->tracking_identifier,'ignite_item_id'=>$viewed_item->id])->all();
					$event_ids = array_column($all_items_events_ids,'event_id');
					$all_events_by_ignite_id = \modules\tracking\models\backend\TrackingSession::find()
						->where(['event_id'=>$event_ids])->orderBy([
							'event_time' => SORT_DESC
						])->all();
					?>
					<tr onclick="showDetails(this)" style="cursor: pointer;">
						<td><?=$viewed_item->year?></td>
						<td><?=$viewed_item->make?></td>
						<td><?=$viewed_item->model?></td>
						<td><?=$viewed_item->trim?></td>
						<td><?=!empty($last_payment_event[0]->event) ? \modules\tracking\widgets\TrackingSessionTimeline::getLastPaymentField('monthly_price', $last_payment_event[0]->event) : ''?></td>
						<td><?=!empty($last_payment_event[0]->event) ? \modules\tracking\widgets\TrackingSessionTimeline::getLastPaymentField('due_on_signing', $last_payment_event[0]->event) : ''?></td>
						<td><?=$all_events_by_ignite_id[0]->event_ip?></td>
						<td><?=date("Y-m-d",$all_events_by_ignite_id[0]->event_time)?></td>
						<td><?=date("H:i:s",$all_events_by_ignite_id[0]->event_time)?></td>
						<td></td>
						<td><i class="fa fa-arrows-v"></i></td>
					</tr>
					<tr style="display: none;">
						<td colspan="11">
							<div class="box box-primary box-solid">
								<div class="box-header with-border">
									<h3 class="box-title">Last Payment Structure</h3>

									<div class="box-tools pull-right">
										<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
										</button>
									</div>
									<!-- /.box-tools -->
								</div>
								<!-- /.box-header -->
								<div class="box-body" style="">
									<?php
										if(!empty($last_payment_event[0]->event)){
											echo \modules\tracking\widgets\TrackingSessionTimeline::widget([
												'models' => [0 => $last_payment_event[0]->event],
											]);
										} else {
											echo "Last Payment not found";
										}
									?>
								</div>
								<!-- /.box-body -->
							</div>

							<div class="box box-default box-solid collapsed-box">
								<div class="box-header with-border">
									<h3 class="box-title">Event Timeline</h3>

									<div class="box-tools pull-right">
										<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
										</button>
									</div>
									<!-- /.box-tools -->
								</div>
								<!-- /.box-header -->
								<div class="box-body" style="">
									<?php
										echo \modules\tracking\widgets\TrackingSessionTimeline::widget([
											'models' => $all_events_by_ignite_id,
										]);
									?>
								</div>
								<!-- /.box-body -->
							</div>
						</td>
					</tr>
				<?php	}
			?>
			</tbody>
		</table>
	</div>
	<!-- /.col -->
</div>