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

    Route::get('/auth/login', 'CheckController@apiCheckLogin')->name('check');

    Route::group(['prefix' => 'user', 'as' => 'user.'], function() {
        Route::get('/profile', 'UserController@show')->name('profile');
        Route::get('/timezones', 'UserController@getTimezones')->name('timezones');
        Route::post('/profile/timezone', 'UserController@updateTimezone')->name('profile.timezone');
    });

    // User bots
    Route::group(['prefix' => 'bots', 'as' => 'bots.'], function () {
        Route::get('/running', 'BotInstanceController@index')->name('running');        Route::put('/running/status', 'BotInstanceController@changeStatus')->name('running.update.status');
    });

    Route::group(['prefix' => 'instances', 'as' => 'instances.'], function () {
        Route::post('/launch', 'BotInstanceController@launchInstance')->name('launch');
    });

    // User schedules
    Route::group(['prefix' => 'schedules', 'as' => 'schedules.'], function () {
        Route::put('/status', 'ScheduleController@changeSchedulingStatus')->name('update.status');
        Route::delete('/details/delete', 'ScheduleController@deleteSchedulerDetails')->name('details.delete');
    });

    Route::group(['prefix' => 'subscriptions', 'as' => 'subscriptions.'], function () {
        Route::get('/', 'SubscriptionController@index')->name('index');
    });

    Route::resources([
        'bots'      => 'BotController',
        'schedules' => 'ScheduleController',
    ]);

});

Route::group(['prefix' => 'admin', 'namespace' => 'Admin', 'middleware' => ['auth:api', 'api.admin']], function() {

    Route::group(['prefix' => 'bots', 'as' => 'bots.'], function () {
        Route::get('/running', 'BotInstanceController@index')->name('running');
        Route::put('/running/status', 'BotInstanceController@changeStatus')->name('running.update.status');
    });

    Route::group(['prefix' => 'instances', 'as' => 'instances.'], function () {
        Route::post('/launch', 'BotInstanceController@launchInstance')->name('launch');
        Route::get('/sync', 'BotInstanceController@syncInstances')->name('sync');
        Route::put('/{id}', 'BotInstanceController@update')->name('update');
    });

    Route::group(['prefix' => 'histories', 'as' => 'histories.'], function () {
        Route::get('/', 'InstanceSessionHistoriesController@index')->name('index');
    });

    Route::resources([
        'user'          => 'UserController',
        'bots'          => 'BotController',
        'schedules'     => 'ScheduleInstanceController',
        'notification'  => 'NotificationController'
    ]);
});
