<?php require "../private/autoload.php";?>
<!doctype html>
<html lang="en" ng-app="kedApp" ng-controller="GlobalController">
<head>
	<meta charset="UTF-8">
<!--	<base href="/">-->
	<meta name="viewport"
		  content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>{{metadata.title}}</title>
	<link rel="stylesheet" href="node_modules/bootstrap/dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="node_modules/font-awesome/css/font-awesome.css">
	<link rel="stylesheet" href="node_modules/ionicons/dist/css/ionicons.css">
	<link rel="stylesheet" href="node_modules/jvectormap/jquery-jvectormap.css">
	<link rel="stylesheet" href="node_modules/admin-lte/dist/css/AdminLTE.css">
	<link rel="stylesheet" href="node_modules/admin-lte/dist/css/skins/_all-skins.css">
	<link rel="stylesheet" href="node_modules/datatables.net-bs/css/dataTables.bootstrap.min.css">
	<link rel="stylesheet" href="node_modules/angular-datepicker/dist/index.css">

	<script src="node_modules/jquery/dist/jquery.min.js"></script>
	<script src="node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
	<script src="assets/js/main.js"></script>
	<script src="node_modules/fastclick/lib/fastclick.js"></script>
	<script src="node_modules/admin-lte/dist/js/adminlte.min.js"></script>
	<script src="node_modules/jquery-sparkline/jquery.sparkline.min.js"></script>
	<script src="node_modules/jvectormap/jquery-jvectormap.min.js"></script>
	<script src="node_modules/chart.js/Chart.min.js"></script>


	<script src="node_modules/angular/angular.min.js"></script>
	<script src="node_modules/angular-route/angular-route.min.js"></script>
	<script src="app.js"></script>
	<script src="node_modules/angular-datepicker/dist/index.js"></script>
	<script src="controllers/MainController.js"></script>
	<script src="controllers/KedController.js"></script>
	<script src="controllers/GlobalController.js"></script>
	<script src="directives/mainDirectives.js"></script>


	<script src="services/KedService.js"></script>

</head>
<body class="skin-green">
<div class="wrapper">
	<main-header></main-header>
	<div class="content-wrapper">
		<content-header></content-header>
		<div ng-view></div>
	</div>
	<left-sidebar></left-sidebar>
	<right-sidebar></right-sidebar>
</div>
</body>
</html>