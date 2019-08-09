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
        Route::get('/profile', 'UserController@show')->name('profile');
        Route::get('/timezones', 'UserController@getTimezones')->name('timezones');
        Route::post('/profile/timezone', 'UserController@updateTimezone')->name('profile.timezone');
    });

    // User bots
    Route::group(['prefix' => 'bots', 'as' => 'bots.'], function () {
        Route::get('/running', 'BotInstancesController@index')->name('running');
        Route::put('/running/status', 'BotInstancesController@changeStatus')->name('running.update.status');
    });

    Route::group(['prefix' => 'instances', 'as' => 'instances.'], function () {
        Route::post('/launch', 'BotInstancesController@launchInstance')->name('launch');
    });

    // User scheduling
    Route::group(['prefix' => 'scheduling', 'as' => 'scheduling.'], function () {
        Route::put('/status', 'SchedulesController@changeSchedulingStatus')->name('update.status');
        Route::delete('/details/delete', 'SchedulesController@deleteSchedulerDetails')->name('details.delete');
    });

    Route::resources([
        'bots'          => 'BotsController',
        'scheduling'    => 'SchedulesController',
    ]);

});

Route::group(['prefix' => 'admin', 'namespace' => 'Admin', 'middleware' => ['auth:api', 'api.admin']], function() {

    Route::group(['prefix' => 'users', 'as' => 'user.'], function() {
        Route::post('/update/credit', 'UsersController@updateCredit')->name('update.credit');
    });

    Route::group(['prefix' => 'bots', 'as' => 'bots.'], function () {
        Route::get('/running', 'BotInstancesController@index')->name('running');
        Route::put('/running/status', 'BotInstancesController@changeStatus')->name('running.update.status');
    });

    Route::group(['prefix' => 'instances', 'as' => 'instances.'], function () {
        Route::post('/launch', 'BotInstancesController@launchInstance')->name('launch');
        Route::get('/sync', 'BotInstancesController@syncInstances')->name('sync');
    });

    Route::group(['prefix' => 'scheduling', 'as' => 'scheduling.'], function () {
    });

    Route::group(['prefix' => 'histories', 'as' => 'histories.'], function () {
        Route::get('/', 'InstanceSessionHistoriesController@index')->name('index');
    });

    Route::resources([
        'users'         => 'UsersController',
        'bots'          => 'BotsController',
        'scheduling'    => 'SchedulingInstancesController',
    ]);

});
