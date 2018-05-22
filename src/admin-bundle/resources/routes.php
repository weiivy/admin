<?php

use Rain\Route;

Route::any('login', 'Rain\Bundle\AdminBundle\Controller\DefaultController@login');
Route::any('register', array('as' => 'register', 'Rain\Bundle\AdminBundle\Controller\DefaultController@register'));
Route::any('forgot', array('as' => 'forgot', 'Rain\Bundle\AdminBundle\Controller\DefaultController@forgot'));
Route::any('password-reset', array('as' => 'password-reset', 'Rain\Bundle\AdminBundle\Controller\DefaultController@passwordReset'));

Route::get('/', array('before' => 'login', 'Rain\Bundle\AdminBundle\Controller\DefaultController@index'));
Route::get('logout', array('before' => 'login', 'Rain\Bundle\AdminBundle\Controller\DefaultController@logout'));

