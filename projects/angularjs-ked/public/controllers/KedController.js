app.controller('KedController', ['$scope', 'KedService', function($scope,KedService) {
	$scope.kedSorting = '-ked_rate';
	$scope.kedSort = function(row_name){
		if($scope.kedSorting.charAt(0) === '-'){
			$scope.kedSorting = row_name
		} else {
			$scope.kedSorting= '-'+row_name
		}
	}

	$scope.newKed = {

	};

	$scope.addKed = function(){
		var ked_params = {
			'ked_name': $scope.newKed.name,
			'grow_start': $scope.newKed.grow_start,
			'grow_end': $scope.newKed.grow_end,
			'fall_end': $scope.newKed.fall_end,
		};

		KedService.addKed(ked_params).then(function (data) {
			$scope.getKeds();
		}).catch(function (response) {

		})
	};

	$scope.getKeds = function(){
		$scope.keds = [];
		KedService.fetchAll().then(function (data) {
			data.data.forEach(function(item, i, arr) {
				$scope.keds.push(item);
			});
		}).catch(function (response) {

		})
	};
	$scope.getKeds();


}])

