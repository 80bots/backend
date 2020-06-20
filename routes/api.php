<?php

use Illuminate\Support\Facades\Route;

Route::get('ping', 'AppController@ping');

Route::get('password/show', 'AppController@apiEmpty')->name('password.reset');
//Route::get('user', 'AppController@apiEmpty')->name('login');

Route::group(['prefix' => 'posts', 'as' => 'posts.'], function () {
    Route::get('/show', 'PostController@showBySlug')->name('slug');
});


// Authentication Routes. Auth::routes() is not used to not provide unneeded routes
Route::group(['prefix' => 'auth', 'as' => 'auth.', 'namespace' => 'Auth'], function () {
    Route::post('login', 'LoginController@apiLogin')->name('login');
    Route::post('register', 'RegisterController@apiRegister')->name('register');
    Route::post('password/email', 'ForgotPasswordController@sendResetLinkEmail')->name('password.email');
    Route::post('password/reset', 'ResetPasswordController@reset')->name('password.reset');
});

Route::group(['middleware' => ['auth:api', 'api.sentry', 'api.instance']], function () {

    Route::get('/auth/login', 'CheckController@apiCheckLogin')->name('check');

    Route::group(['prefix' => 'user', 'as' => 'user.'], function () {
        Route::get('/profile', 'UserController@show')->name('profile');
        Route::put('/profile', 'UserController@update')->name('update.profile');
        Route::get('/timezone', 'UserController@getTimezones')->name('timezones');
        Route::post('/profile/timezone', 'UserController@updateTimezone')->name('profile.timezone');
        Route::post('/feedback', 'UserController@feedback')->name('feedback');
    });

    // User bots
    Route::group(['prefix' => 'bots', 'as' => 'bots.'], function () {
        Route::get('/', 'BotController@index')->name('running');
//        Route::get('/running', 'BotInstanceController@index')->name('running');
        Route::put('/running/status', 'BotInstanceController@changeStatus')->name('running.update.status');
    });

    Route::group(['prefix' => 'instances', 'as' => 'instances.', 'namespace' => 'Common\Instances'], function () {
        Route::get('/', 'InstanceController@index')->name('running');
        Route::get('/regions', 'InstanceController@regions')->name('regions');
        Route::post('/launch', 'ManageController@launchInstances')->name('launch');
        Route::post('/restore', 'ManageController@restoreInstance')->name('restore');
        Route::post('/copy', 'ManageController@copy')->name('copy');

        Route::put('/{id}', 'InstanceController@update')->name('update');
        Route::get('/{id}', 'InstanceController@show')->name('get');
        Route::post('/{id}/report', 'InstanceController@reportIssue')->name('report');

        Route::get('/{instance_id}/objects', 'FileSystemController@getS3Objects');
        Route::get('/{instance_id}/objects/{id}', 'FileSystemController@getS3Object');

    });

    // User schedules
    Route::group(['prefix' => 'schedules', 'as' => 'schedules.'], function () {
        Route::put('/status', 'ScheduleController@changeSchedulingStatus')->name('update.status');
        Route::delete('/details/delete', 'ScheduleController@deleteSchedulerDetails')->name('details.delete');
    });

    Route::group(['prefix' => 'subscriptions', 'as' => 'subscriptions.'], function () {
        Route::get('/', 'SubscriptionController@index')->name('index');
        Route::post('/subscribe', 'SubscriptionController@subscribe')->name('subscribe');
    });

    Route::group(['prefix' => 'platform', 'as' => 'platform.'], function () {
        Route::get('/types', 'PlatformController@getInstanceTypes');
    });

    Route::group(['prefix' => 'posts', 'as' => 'posts.'], function () {
        Route::get('/', 'PostController@index')->name('posts');
        Route::post('/', 'PostController@store')->name('store');
        Route::put('/{id}', 'PostController@update')->name('update');
        Route::delete('/{id}', 'PostController@delete')->name('delete');
        Route::get('/{id}', 'PostController@show')->name('show');
    });

    Route::resources([
        'schedule' => 'ScheduleController',
        'platform' => 'PlatformController'
    ]);
});

Route::group([
    'prefix' => 'admin',
    'namespace' => 'Admin',
    'middleware' => ['auth:api', 'api.admin'],
    'as' => 'admin.'
], function () {

    Route::group(['prefix' => 'aws', 'as' => 'aws.'], function () {
        Route::get('/', 'AwsSettingController@index')->name('aws');
        Route::put('/{setting}', 'AwsSettingController@update')->name('update.settings');
    });

    Route::group(['prefix' => 'bots', 'as' => 'bots.'], function () {
        Route::get('/running', 'BotInstanceController@index')->name('running');
        Route::get('/tags', 'BotController@getTags')->name('tags');
        Route::get('/sync', 'BotController@syncBots')->name('sync');
    });

    Route::group([
        'prefix' => 'instances',
        'as' => 'instances.',
    ], function () {
        Route::get('/regions', 'BotInstanceController@regions')->name('regions');
        Route::put('/regions/{id}', 'BotInstanceController@updateRegion')->name('update.region');
        Route::get('/regions/sync', 'BotInstanceController@syncRegions')->name('sync.regions');
        Route::get('/amis', 'BotInstanceController@amis')->name('amis');
        Route::get('/pem', 'BotInstanceController@getInstancePemFile')->name('pem');
        Route::post('/launch', 'BotInstanceController@launchInstances')->name('launch');
        Route::post('/restore', 'BotInstanceController@restoreInstance')->name('restore');
        Route::get('/sync', 'BotInstanceController@syncInstances')->name('sync');
        Route::put('/{id}', 'BotInstanceController@update')->name('update');
        Route::get('/{id}', 'BotInstanceController@show')->name('show');
    });

    Route::resources([
        'aws' => 'AwsSettingController',
        'user' => 'UserController',
        'bots' => 'BotController',
        'schedule' => 'ScheduleInstanceController',
        'notification' => 'NotificationController',
        'subscription' => 'SubscriptionController',
        'session' => 'InstanceSessionController'
    ]);
});
