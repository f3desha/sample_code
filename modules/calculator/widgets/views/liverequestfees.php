<?php
	echo '<div class="col-sm-4" ng-hide="fee.isDeleted" ng-repeat="fee in liverequestFees.collection track by $index">' .
		'<div class="row"><div class="col-lg-6 col-md-7"><div class="form-group"><input placeholder="Fee Name" type="text" class="form-control" ng-disabled="editMode == 1 || fee.type != 2" ng-model="fee.name" /><div class="help-block"></div></div></div><div class="col-lg-4 col-md-3"><div class="form-group"><input type="text" ng-model="fee.value" ng-disabled="editMode == 1" class="form-control" /><div class="help-block"></div></div></div><div class="col-lg-1 col-md-1"><span ng-click="liverequestFees.delete($index)"><i style="padding-top: 10px; margin-left: 0px; cursor: pointer; color: #2b9af3;" ng-show="fee.type == 2 && editMode == 2" class="fa fa-trash" aria-hidden="true"></i></span></div></div><div class="form-group">' .
		'<label ng-style="{\'visibility\': (fee.type == 1 || fee.type == 2) && widgetPreloader.widgetSuccessfullyLoaded == true ? \'visible\' : \'hidden\'}" ><input type="checkbox" ng-model="fee.is_taxable" ng-disabled="editMode == 1" aria-invalid="false"> Taxable</label>' .
		'<div class="help-block"></div>' .
		'</div></div>';
	echo '<div class="col-sm-4" ng-style="{\'visibility\': editMode == 2 ? \'visible\' : \'hidden\'}"><span ng-click="liverequestFees.add()"><i style="cursor: pointer; color: #2b9af3;" class="fa fa-plus-circle fa-1g" aria-hidden="true"></i></span></div>';
	?>


