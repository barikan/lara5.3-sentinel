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
    return view('welcome');
});

// ログイン用のルート
Route::get('login', function() {return view('auth.login', [
    'info' => session('info'),
    'myerror' => session('myerror')
]);})->name('login');
Route::post('login', 'Auth\LoginController@login');

// ログアウト用のルート
Route::match(['get', 'post'], 'logout', 'Auth\LoginController@logout')->name('logout');

// ユーザー登録用のルート
Route::get('register', function() {return view('auth.register');});
Route::post('register', 'Auth\RegisterController@register');

// アクティベーション
Route::get('activate/{email}/{code}', 'Sentinel\ActivateController@activate');

Route::get('/home', 'HomeController@index');