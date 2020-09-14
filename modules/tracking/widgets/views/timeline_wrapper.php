<?php
use modules\tracking\widgets\TrackingSessionTimeline;
	if(!empty($screens_collection)){ ?>
		<ul class="timeline">
			<?php
				foreach ($screens_collection as $i => $screen) {
					?>
					<!-- timeline time label -->
					<li class="time-label">
                  <span class="bg-green">
                    Screen <?=$i?>
                  </span>
					</li>
					<!-- /.timeline-label -->
					<!-- timeline item -->
					<?php
					foreach ($screen as $item){
						echo $this->render(TrackingSessionTimeline::renderEvent($item),[
							'event' => $item
						]);
					}

					?>

					<?php
				}
			?>
			<li>
				<i class="fa fa-clock-o bg-gray"></i>
			</li>
		</ul>
	<?php } else {
		echo "Events not found";
	}
?>


