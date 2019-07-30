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

Route::get('/', 'HomeController@login');


Auth::routes();
Route::get('CalUserCreditScore', 'AppController@CalUserCreditScore')->name('CreditScoreEmail');

//Route::get('/home', 'HomeController@index')->name('home');
Route::get('/user-activation/{id}', 'AppController@UserActivation')->name('user-activation');

Route::group(['middleware' => ['auth']], function() {
    Route::get('profile/{id}', 'UserController@show')->name('user.profile');
    Route::post('update/timezone', 'UserController@updateTimezone')->name('user.update.timezone');
});

include('admin.php');
include('user.php');

Route::post(
    'stripe/webhook',
    '\App\Http\Controllers\WebhookController@handleWebhook'
);


//Route::get('stripe-payment', 'StripeController@SendPayment')->name('stripe-payment');
