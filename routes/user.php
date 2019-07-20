<?php
Route::group(['prefix' => 'user', 'as' => 'user.', 'middleware' => ['auth', 'user']], function(){
    Route::post('/checkBotIdInQueue','UserInstancesController@checkBotIdInQueue');
    Route::post('/dispatch/launch-instance','UserInstancesController@dispatchLaunchInstance')->name('dispatch.launch_instance');
    Route::get('dashboard', 'UserController@index')->name('dashboard');
    Route::get('profile/{id}', 'UserController@show')->name('profile');
    Route::post('update/timezone', 'UserController@updateTimezone')->name('user.update.timezone');
    Route::get('cal-used-credit', 'AppController@CalUsedCredit')->name('cal-used-credit');
    Route::get('cal-up-time', 'AppController@CalInstancesUpTime')->name('cal-up-time');

    Route::get('cron-start-scheduling','AppController@startScheduling')->name('cron-start-scheduling');
    Route::get('cron-stop-scheduling','AppController@stopScheduling')->name('cron-stop-scheduling');

    Route::get('cron-scheduling','AppController@Scheduling')->name('cron-scheduling');

    Route::get('bots', 'BotsController@index')->name('bots.list');
    Route::get('bots/{platformId}/all', 'BotsController@index')->name('bots.all.list');

    Route::resource('scheduling', 'SchedulingInstancesController');
    Route::group(['prefix' => 'scheduling', 'as' => 'scheduling.'], function() {
        Route::get('check-scheduled/{id}', 'SchedulingInstancesController@CheckScheduled')->name('check-scheduled');
        Route::post('change-status', 'SchedulingInstancesController@changeStatus')->name('change-status');
        Route::get('convert-time-utc-to-user/{str}/{userTimezone}', 'SchedulingInstancesController@convertTimeToUSERzone')->name('convert-time-utc-to-user');
        Route::post('delete-scheduler-details', 'SchedulingInstancesController@deleteSchedulerDetails')->name('delete-scheduler-details');
    });


    /* added at 09/07/2019 by sandip START*/
    Route::group(['middleware' => ['web']], function () {
        Route::resource('instance','UserInstancesController');
    });
    Route::any('storeSession','UserInstancesController@storeBotIdInSession')->name('storeSession');
    /* END */

    Route::group(['prefix' => 'instance', 'as' => 'instance.'], function() {
        Route::post('change-status', 'UserInstancesController@changeStatus')->name('change-status');
    });

    Route::resource('subscription-plans', 'SubscriptionPlanController');

    Route::post('subscribe', 'StripeController@createSubscription');
    Route::post('subscription', 'StripeController@createSubscription')->name('subscription.create');
    Route::post('change-subscription', 'StripeController@swapSubscriptionPlan')->name('subscription.swap');

    Route::get('forums/like-discussion/{discussion_id}', 'ForumDiscussionController@likeDiscussion');
    Route::get('forums/dislike-discussion/{discussion_id}', 'ForumDiscussionController@dislikeDiscussion');
});
