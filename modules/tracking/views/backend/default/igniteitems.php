<?php
if(!empty($lead)) {
	if (empty($tracking_tab)) {
		$box = \modules\themes\admin\widgets\Box::begin(
			[
				'title'       => 'Ignite Items Tracking',
				'renderBody'  => false,
				'options'     => [
					'class' => 'box-success'
				],
				'bodyOptions' => [
					'class' => ''
				],
			]
		);
		$box->beginBody();
	}
	?>
	<script>
		function showDetails(elem) {
			$(elem).closest('tr').next('tr').toggle('slow');
		}
	</script>
	<div class="row">
		<div class="col-xs-12 table-responsive">
			<table class="table table-striped">
				<thead>
				<tr>
					<th>First Name</th>
					<th>Last Name</th>
					<th>Email</th>
					<th>Zip</th>
					<th>Phone</th>
					<th>Lead Date</th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td><?= $lead->name ?></td>
					<td><?= $lead->surname ?></td>
					<td><?= $lead->email ?></td>
					<td><?= $lead->zip ?></td>
					<td><?= $lead->phone ?></td>
					<td><?= date("Y-m-d H:i:s", $lead->created_at) ?></td>
				</tr>
				</tbody>
			</table>
		</div>
		<!-- /.col -->
	</div>
	<h4>Vehicles Unlocked</h4>
	<?php
	echo $this->render('vehicleslist', [
		'lead'         => $lead,
		'items_models' => $unlocked_items_models
	]); ?>

	<h4>Vehicles Viewed</h4>
	<?php
	echo $this->render('vehicleslist', [
		'lead'         => $lead,
		'items_models' => $viewed_items_models
	]);
	echo \yii\widgets\LinkPager::widget([
		'pagination' => $viewed_items_pages,
	]);
	if (empty($tracking_tab)) {
		$box->endBody();
	}
}
