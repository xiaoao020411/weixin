<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    echo phpinfo();
});
Route::prefix('wx')->group(function(){
    Route::get('/','Index\WxController@Token');
    Route::any('/','Index\WxController@wxEvent');
    Route::get('/token','Index\WxController@getAccessToken');
    Route::get('/guzzle2','Index\WxController@guzzle2');
    Route::get('/menu','Index\WxController@createMenu');
    Route::any('/weather','Index\WxController@weather');
    Route::get('/getWx','Index\WxController@getWxUserInfo');
    Route::get('/test','Index\XcxController@test');
    Route::get('/login','Index\XcxController@login');
    Route::get('/goods','Index\XcxController@goods');
    Route::get('/list','Index\XcxController@list');
});

Route::prefix('test')->group(function(){
    Route::get('/guzzle1','Index\TestController@guzzle1');
    Route::get('/guzzle2','Index\TestController@guzzle2');
});

Route::get('/huizi','Index\TestController@huizi');
Route::get('/home','Index\XcxController@home');