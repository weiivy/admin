/**
 * Created by A01 on 2015/8/14.
 */
var routeApp = angular.module('routeApp',['ngRoute'], function($httpProvider){
     $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
});
routeApp.config(function ($routeProvider) {
      $routeProvider
      .when('/product/index', {
        templateUrl: 'view/product/list.html',
        controller: 'ProductListCrl'
      })
      .when('/product/info/:id', {
          templateUrl: 'view/product/info.html',
          controller: 'ProductInfoCtl'
      });
});
