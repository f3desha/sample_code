var app = angular.module('kedApp', [
	'ngRoute',
	'datePicker'
]);

app.config(function($routeProvider, $httpProvider) {
	$httpProvider.defaults.transformRequest = function(data){
		if (data === undefined) {
			return data;
		}
		return $.param(data);
	};
	$httpProvider.defaults.headers.post['Content-Type'] = ''
		+ 'application/x-www-form-urlencoded; charset=UTF-8';

	$routeProvider
		.when("/", {
			templateUrl : "templates/main.html",
			controller: "MainController"
		})
		.when("/keds", {
			templateUrl : "templates/ked.html",
			controller: "KedController"
		})
// configure html5 to get links working on jsfiddle
// 	$locationProvider.html5Mode(true);
});