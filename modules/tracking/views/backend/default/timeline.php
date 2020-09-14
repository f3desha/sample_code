<?php
	use modules\tracking\widgets\TrackingSessionTimeline;

	echo TrackingSessionTimeline::widget([
		'models' => $models,
	]);

	echo \yii\widgets\LinkPager::widget([
		'pagination' => $pages,
	]);
?>
