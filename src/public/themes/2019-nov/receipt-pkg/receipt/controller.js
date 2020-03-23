app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.
    when('/receipts', {
        template: '<receipts></receipts>',
        title: 'Receipts',
    });
}]);

app.component('receipts', {
    templateUrl: receipt_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        $http({
            url: laravel_routes['getReceipts'],
            method: 'GET',
        }).then(function(response) {
            self.receipts = response.data.receipts;
            $rootScope.loading = false;
        });
        $rootScope.loading = false;
    }
});