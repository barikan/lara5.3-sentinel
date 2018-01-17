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
    'info' => session('info')
])->withErrors(session('myerror'));})->name('login');
Route::post('login', 'Auth\LoginController@login');

// ログアウト用のルート
Route::match(['get', 'post'], 'logout', 'Auth\LoginController@logout')->name('logout');

// ユーザー登録用のルート
Route::get('register', function() {return view('auth.register');});
Route::post('register', 'Auth\RegisterController@register');
Route::get('register/{email}', 'Auth\RegisterController@resendActivationCode');

// アクティベーション
Route::get('activate/{email}/{code}', 'Sentinel\ActivateController@activate');

// パスワードのリセット用のルート
Route::get('password/email', 'Auth\ForgotPasswordController@showLinkRequestForm'); //->name('password.email');
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset.token');
Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm');
Route::post('password/reset', 'Auth\ResetPasswordController@reset')->name('password.reset');

Route::resource('users', 'Sentinel\UserController');

Route::resource('roles', 'Sentinel\RoleController');
Route::post('permissions', 'Sentinel\PermissionController@store');
Route::delete('permissions/{name}', 'Sentinel\PermissionController@destroy');

Route::get('/home', 'HomeController@index');
