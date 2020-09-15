app.directive("contentHeader",function () {
	return {
		templateUrl: 'directives/templates/contentheader.html',
		replace: true
	}
});
app.directive("addKed",function () {
	return {
		templateUrl: 'directives/templates/addked.html',
		replace: true
	}
});
app.directive("kedWidget", function () {
	return {
		restrict: 'AE',
		templateUrl: 'directives/templates/kedwidget.html',
		scope: {
			keds: '=',
			kedSortFunction: '&',
			kedSorting: '='
		}
	}
});

app.directive("kedWidgetFilters", function () {
	return {
		templateUrl: 'directives/templates/kedwidgetfilters.html',
		replace: true
	}
});

app.directive("kedWidgetPagination", function () {
	return {
		templateUrl: 'directives/templates/kedwidgetpagination.html',
		replace: true
	}
});

app.directive("leftSidebar", function () {
	return {
		templateUrl: 'directives/templates/leftsidebar.html'
	}
})

app.directive("leftSidebarUserPanel",function () {
	return {
		templateUrl: 'directives/templates/leftsidebaruserpanel.html',
		replace: true
	}
});

app.directive("mainHeader",function () {
	return {
		templateUrl: 'directives/templates/mainheader.html'
	}
});

app.directive("rightSidebar",function () {
	return {
		templateUrl: 'directives/templates/rightsidebar.html'
	}
});

app.directive("sidebarItem",function () {
	return {
		templateUrl: 'directives/templates/sidebaritem.html',
		replace: true,
		transclude: true,
		scope: {
			link: '@',
			active: '@'
		}
	}
});