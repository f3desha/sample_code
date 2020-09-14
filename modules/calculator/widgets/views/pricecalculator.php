<?php
use common\components\calculator\widgets\CalculatorWidgetAsset;

?>
	<?php echo '<div ng-controller="CalculatorController" ng-style="{\'visibility\': widgetPreloader.widgetSuccessfullyLoaded ? \'visible\' : \'hidden\'}" ng-init="initialization(\''.htmlspecialchars(json_encode($custom_config)).'\')">'; ?>
<div class="bigloader" ng-style="{'visibility': widgetPreloader.widgetSuccessfullyLoaded == false ? 'visible' : 'hidden'}"></div>
	<?=$content?>
	</div>

