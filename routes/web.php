<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Authentication routes
Route::view('/', 'auth.login');
Auth::routes();

// General routes
Route::get('/profile', 'UserController@show')->name('profile');

// User routes
Route::group(['middleware' => ['auth', 'user']], function () {
    Route::group(['prefix' => 'bots', 'as' => 'bots.'], function () {
        Route::get('/', 'BotsController@index')->name('index');
        Route::get('/running', 'BotInstanceController@index')->name('running');
        Route::put('/running/status', 'BotInstanceController@changeStatus')->name('running.update.status');
        Route::post('/running/dispatch', 'BotInstanceController@dispatchLaunchInstace')->name('running.dispatch');
        Route::get('/check', 'BotInstanceController@checkBotIdInQueue')->name('running.check');
    });

    Route::group(['prefix' => 'user', 'as' => 'user.'], function() {
        Route::get('/', 'UserController@index')->name('index');
        Route::put('/{id}/status', 'UserController@changeStatus')->name('update.status');
        Route::put('/{id}/timezone', 'UserController@updateTimezone')->name('update.timezone');
    });

    Route::group(['prefix' => 'scheduling', 'as' => 'scheduling.'], function () {
        Route::delete('/details', 'ScheduleController@deleteSchedulerDetails')->name('delete.details');
        Route::put('/status', 'ScheduleController@changeStatus')->name('update.status');
        Route::get('/{id}/check', 'ScheduleController@checkScheduled')->name('check');
        Route::post('/convert', 'SchedulingInstancesController@convertTimeToUSERzone')->name('convert.zone');
    });

    Route::group(['prefix' => 'session', 'as' => 'session.'], function () {
        Route::get('/', 'InstanceSessionHistoryController@index')->name('index');
        Route::post('/', 'BotInstanceController@storeBotIdInSession')->name('create');
    });

    Route::resource('scheduling', 'ScheduleController');
});

// Admin routes
Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'admin'], 'namespace' => 'Admin', 'as' => 'admin.'], function () {
    Route::group(['prefix' => 'bots', 'as' => 'bots.'], function () {
        Route::get('/running', 'BotInstanceController@index')->name('running');
        Route::put('/{id}/status', 'BotsController@changeStatus')->name('update.status');
        Route::get('/running/{userId}', 'BotInstanceController@index')->name('user.running');
        Route::put('/running/status', 'BotInstanceController@changeStatus')->name('running.update.status');
        Route::post('/running/dispatch', 'BotInstanceController@dispatchLaunchInstace')->name('running.dispatch');
        Route::get('/check', 'BotInstanceController@checkBotIdInQueue')->name('running.check');
    });

    Route::group(['prefix' => 'scheduling', 'as' => 'scheduling.'], function () {
        Route::put('/status', 'SchedulingInstancesController@changeStatus')->name('change-status');
        Route::put('/delete-details', 'SchedulingInstancesController@deleteSchedulerDetails')->name('delete-scheduler-details');
        Route::get('/check-scheduled/{id}', 'SchedulingInstancesController@checkScheduled')->name('check-scheduler');
    });

    Route::group(['prefix' => 'user', 'as' => 'user.'], function() {
        Route::get('/', 'UserController@index')->name('index');
        Route::put('/{id}/status', 'UserController@changeStatus')->name('update.status');
        Route::put('/{id}/timezone', 'UserController@updateTimezone')->name('update.timezone');
    });

    Route::group(['prefix' => 'session', 'as' => 'session.'], function () {
        Route::get('/', 'InstanceSessionHistoryController@index')->name('index');
        Route::post('/', 'BotInstanceController@storeBotIdInSession')->name('create');
    });

    Route::group(['prefix' => 'subscription', 'as' => 'subscription.'], function () {
    });

    Route::resource('bots', 'BotsController');
    Route::resource('scheduling', 'SchedulingInstancesController');
});

Route::get('/user/activation/{token}', 'AppController@UserActivation')->name('user-activation');
