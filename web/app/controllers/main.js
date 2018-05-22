/**
 * Created by A01 on 2015/8/14.
 */
/*
routeApp.controller('RouteListCtl',function($scope) {
    $scope.array = [1,2,3,4,5];
});
routeApp.controller('RouteDetailCtl',function($scope, $routeParams) {
    $scope.id = $routeParams.id;
});
*/

/* 加载服务列表 */
routeApp.controller('ProductListCrl', function($scope, $http) {

    $scope.array = [];
    $scope.flag = false;
    $scope.unflag = true;
    $http.get('../api.php/product/list', {})
    .success(function(data) {
        if(data.status == 1 && data.length != 0) {
            $scope.array = data.data;
            $scope.flag = true;
            $scope.unflag = false;
        }
    }).error(function(data){
        console.info("错误");
    });
});

/* 加载服务详情 */
routeApp.controller('ProductInfoCtl', function($scope, $http, $routeParams){
    $scope.product = [];
    $http.get("../api.php/product/info", {id:$routeParams.id})
        .success(function(data){

        })
        .error(function(data){

        });
});