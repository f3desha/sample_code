app.factory("KedService", ['$http', function ($http) {
	return {
		//Main function for getting items
		fetchAll: function () {
			return $http({
				method: "POST", url: "/api", data: {

				}
			});

		},
		addKed: function (ked_params) {
			return $http({
				method: "POST", url: "/api",
				data: ked_params
			});

		}
	}
}]);