<?php
	echo '<div class="col-sm-12" >' .
		'<div class="row" ng-hide="rate.isDeleted" ng-repeat="rate in liverequestUsedRates.collection track by $index">
<div class="col-lg-4">
<div class="form-group">
<label>Month From</label>
<input ng-disabled="editMode == 1" ng-model="rate.month_from" placeholder="Month From" type="text" class="form-control"  />
<div class="help-block"></div></div></div>
<div class="col-lg-4">
<div class="form-group">
<label>Month To</label>
<input ng-disabled="editMode == 1" ng-model="rate.month_to" placeholder="Month To" type="text" class="form-control" />
<div class="help-block"></div></div></div>
<div class="col-lg-3">
<div class="form-group">
<label>Value</label>
<input ng-disabled="editMode == 1" ng-model="rate.value" placeholder="Value" type="text" class="form-control" />
<div class="help-block"></div></div></div>
<div class="col-lg-1"><span ng-click="liverequestUsedRates.delete($index)"><i style="padding-top: 35px; margin-left: 0px; cursor: pointer; color: #2b9af3;" ng-show="editMode == 2" class="fa fa-trash" aria-hidden="true"></i></span></div></div><div class="form-group">' .
		'<div class="help-block"></div>' .
		'</div></div>';
	echo '<div class="col-sm-4"><span ng-click="liverequestUsedRates.add()"><i style="cursor: pointer; color: #2b9af3;" class="fa fa-plus-circle fa-1g" ng-show="editMode == 2" aria-hidden="true"></i></span></div>';
	?>


