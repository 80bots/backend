<?php

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['auth','admin'], 'namespace' => 'admin'], function(){
    Route::get('dashboard', 'UserController@index')->name('dashboard');
    Route::post('/checkBotIdInQueue','UserInstancesController@checkBotIdInQueue');

    Route::group(['prefix' => 'user', 'as' => 'user.'], function() {
        Route::get('/', 'UserController@index')->name('index');
        Route::post('change-status', 'UserController@changeStatus')->name('change-status');
        Route::post('update-credit', 'UserController@updateCredit')->name('update-credit');

        Route::group(['prefix' => 'instance', 'as' => 'instance.'], function() {
            Route::any('list/{id}', 'UserInstancesController@index')->name('list');
            Route::get('running', 'UserInstancesController@runningInstances')->name('running');
            Route::post('change-status', 'UserInstancesController@changeStatus')->name('change-status');
        });
    });

    Route::group(['prefix' => 'instance', 'as' => 'instance.'], function() {
        Route::get('running', 'UserInstancesController@runningInstances')->name('running');
        Route::post('change-status', 'UserInstancesController@changeStatus')->name('change-status');
    });

    Route::resource('instance','UserInstancesController');


    Route::group(['prefix' => 'bots'], function() {

        Route::get('list', 'BotsController@list')->name('bots.list');
        Route::get('{platformId}/list', 'BotsController@list')->name('bots.all.list');
        Route::get('mine', 'BotsController@mineBots')->name('my-bots');

        Route::group(['as' => 'bots.'], function() {
          Route::post('change-status', 'BotsController@ChangeStatus')->name('change-status');
        });
    });

    Route::resource('bots','BotsController');

    Route::resource('plan','SubscriptionPlanController');
    Route::group(['prefix' => 'plan', 'as' => 'plan.'], function() {
        Route::post('change-status', 'SubscriptionPlanController@ChangeStatus')->name('change-status');
    });

    Route::get('list-sessions', 'InstanceSessionsHistoryController@index')->name('listsessions');

    Route::resource('percent','CreditPercentController');

    Route::any('storeSession','UserInstancesController@storeBotIdInSession');
    Route::post('/jobStart','UserInstancesController@storeJob')->name('jobStart');


    Route::resource('scheduling', 'SchedulingInstancesController');
    Route::group(['prefix' => 'scheduling', 'as' => 'scheduling.'], function() {
        Route::get('check-scheduled/{id}', 'SchedulingInstancesController@CheckScheduled')->name('check-scheduled');
        Route::post('change-status', 'SchedulingInstancesController@changeStatus')->name('change-status');
        Route::get('convert-time-utc-to-user/{str}/{userTimezone}', 'SchedulingInstancesController@convertTimeToUSERzone')->name('convert-time-utc-to-user');
        Route::post('delete-scheduler-details', 'SchedulingInstancesController@deleteSchedulerDetails')->name('delete-scheduler-details');
    });

});
