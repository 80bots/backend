<?php

use Illuminate\Support\Facades\Route;

Route::get('ping', 'AppController@ping');

Route::get('password/show', 'AppController@apiEmpty')->name('password.reset');

// Authentication Routes. Auth::routes() is not used to not provide unneeded routes
Route::group(['prefix' => 'auth', 'as' => 'auth.', 'namespace' => 'Auth'], function () {
    Route::post('login', 'LoginController@apiLogin')->name('login');
    Route::post('register', 'RegisterController@apiRegister')->name('register');
    Route::post('password/email', 'ForgotPasswordController@sendResetLinkEmail')->name('password.email');
    Route::post('password/reset', 'ResetPasswordController@reset')->name('password.reset');
});

Route::group([
    'middleware' => ['auth:api', 'api.instance']
], function () {

    Route::get('/auth/login', 'CheckController@apiCheckLogin')->name('check');

    Route::group(['prefix' => 'user', 'as' => 'user.'], function () {
        Route::get('/profile', 'UserController@show')->name('profile');
        Route::put('/profile', 'UserController@update')->name('update.profile');
        Route::get('/timezone', 'UserController@getTimezones')->name('timezones');
        Route::post('/profile/timezone', 'UserController@updateTimezone')->name('profile.timezone');
        Route::put('/status/{id}', 'UserController@updateStatus')->name('update.status');
    });

    Route::group(['prefix' => 'schedules', 'as' => 'schedules.'], function () {
        Route::put('/status', 'ScheduleController@changeSchedulingStatus')->name('update.status');
        Route::delete('/details/delete', 'ScheduleController@deleteSchedulerDetails')->name('details.delete');
    });

    Route::group(['prefix' => 'aws', 'as' => 'aws.'], function () {
        Route::get('/', 'AwsSettingController@index')->name('aws');
        Route::put('/{setting}', 'AwsSettingController@update')->name('update.settings');
    });

    Route::group(['prefix' => 'bots', 'as' => 'bots.'], function () {
        Route::get('/', 'BotController@index')->name('running');
        Route::get('/tags', 'BotController@getTags')->name('tags');
        Route::get('/sync', 'BotController@syncBots')->name('sync');
        Route::put('/status/{status}', 'BotController@updateStatus')->name('status');

        Route::group([
            'prefix' => 'instances',
            'as' => 'instances.',
        ], function () {
            Route::get('/pem', 'BotInstanceController@getInstancePemFile')->name('pem');
            Route::get('/sync', 'BotInstanceController@syncInstances')->name('sync');
            Route::get('/regions', 'BotInstanceController@regions')->name('regions');
            Route::put('/regions/{id}', 'BotInstanceController@updateRegion')->name('update.region');
        });
    });


    Route::group(['prefix' => 'botinstances', 'as' => 'botinstances.'], function () {
        Route::get('/{id}', 'BotInstanceController@show')->name('get');
        Route::put('/{id}', 'BotInstanceController@updateInstance');
        Route::post('/restart', 'BotInstanceController@restart');
    });

    Route::group(['prefix' => 'instances', 'as' => 'instances.', 'namespace' => 'Common\Instances'], function () {
        Route::get('/', 'InstanceController@index')->name('running');
        Route::get('/regions', 'InstanceController@regions')->name('regions');
        Route::post('/update', 'InstanceController@updateLastNotification');
        Route::post('/launch', 'ManageController@launchInstances')->name('launch');
        Route::post('/restore', 'ManageController@restoreInstance')->name('restore');
        Route::post('/copy', 'ManageController@copy')->name('copy');

        Route::put('/{id}', 'InstanceController@update')->name('update');
        Route::get('/{id}', 'InstanceController@show')->name('get');
        Route::post('/{id}/report', 'InstanceController@reportIssue')->name('report');

        Route::get('/{instance_id}/objects', 'FileSystemController@getS3Objects');
        Route::get('/{instance_id}/objects/{id}', 'FileSystemController@getS3Object');

    });

    Route::resources([
        'user' => 'UserController',
        'schedule' => 'ScheduleController',
        'session' => 'InstanceSessionController',
        'bots' => 'BotController',
        'botinstances' => 'BotInstanceController',
    ]);
});
