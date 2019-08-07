<?php
use Illuminate\Support\Facades\Route;

// Authentication Routes. Auth::routes() is not used to not provide unneeded routes
Route::group(['prefix' => 'auth', 'as' => 'auth.', 'namespace' => 'Auth'], function() {
    Route::post('login', 'LoginController@apiLogin')->name('login');
    Route::post('register', 'RegisterController@register')->name('register');
    Route::post('password/email', 'ForgotPasswordController@sendResetLinkEmail')->name('password.email');
    Route::post('password/reset', 'ResetPasswordController@reset')->name('password.update');
});

Route::group(['middleware' => ['auth:api']], function() {

    Route::group(['prefix' => 'user', 'as' => 'user.'], function() {
        Route::get('/', 'UserController@index')->name('index');
        Route::get('/timezones', 'UserController@getTimezones')->name('timezones');
    });

    // User bots
    Route::group(['prefix' => 'bots', 'as' => 'bots.'], function () {
        Route::get('/running', 'BotInstancesController@index')->name('running');
        Route::put('/running/status', 'BotInstancesController@changeStatus')->name('running.update.status');
    });

    // User scheduling
    Route::group(['prefix' => 'scheduling', 'as' => 'scheduling.'], function () {
    });

    Route::resources([
        'bots'          => 'BotsController',
        'scheduling'    => 'SchedulesController',
    ]);

});

Route::group(['prefix' => 'admin', 'namespace' => 'Admin', 'middleware' => ['auth:api', 'api.admin']], function() {

    Route::group(['prefix' => 'users', 'as' => 'user.'], function() {
    });

    Route::group(['prefix' => 'bots', 'as' => 'bots.'], function () {
        Route::get('/running', 'BotInstancesController@index')->name('running');
        Route::put('/running/status', 'BotInstancesController@changeStatus')->name('running.update.status');
    });

    Route::group(['prefix' => 'scheduling', 'as' => 'scheduling.'], function () {
    });

    Route::resources([
        'users'         => 'UsersController',
        'bots'          => 'BotsController',
        'scheduling'    => 'SchedulingInstancesController',
    ]);

});
