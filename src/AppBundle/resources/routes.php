<?php

use Rain\Route;
Route::controller('ueditor', 'AppBundle\Controller\UeditorController');
Route::group(array('before' => 'login'), function(){
    Route::controller('member', 'AppBundle\Controller\MemberController');
    Route::controller('order', 'AppBundle\Controller\OrderController');
    Route::controller('bank', 'AppBundle\Controller\BankController');
    Route::controller('bank-config', 'AppBundle\Controller\BankConfigController');
    Route::controller('contact', 'AppBundle\Controller\ContactController');
});
