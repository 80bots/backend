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
    Route::resource('subscription', 'SubscriptionController');

    Route::group(['prefix' => 'bots', 'as' => 'bots.'], function () {
        Route::get('/', 'BotController@index')->name('index');
        Route::get('/all', 'BotController@getAll')->name('all');
        Route::get('/running', 'BotInstanceController@index')->name('running');
        Route::put('/running/status', 'BotInstanceController@changeStatus')->name('running.update.status');
        Route::post('/running/dispatch', 'BotInstanceController@dispatchLaunchInstace')->name('running.dispatch');
        Route::get('/check', 'BotInstanceController@checkBotIdInQueue')->name('running.check');
    });

    Route::group(['prefix' => 'user', 'as' => 'user.'], function() {
        Route::get('/', 'UserController@index')->name('index');
        Route::put('/{id}/status', 'UserController@changeStatus')->name('update.status');
        Route::put('/credit', 'UserController@updateCredit')->name('update.credit');
        Route::put('/{id}/timezone', 'UserController@updateTimezone')->name('update.timezone');
    });

    Route::group(['prefix' => 'scheduling', 'as' => 'scheduling'], function () {
        Route::get('/', 'ScheduleController@index')->name('.index');
        Route::post('/store', 'ScheduleController@store')->name('.store');
        Route::delete('/details', 'ScheduleController@deleteSchedulerDetails')->name('.delete.details');
        Route::put('/status', 'ScheduleController@changeStatus')->name('.update.status');
    });

    Route::group(['prefix' => 'session', 'as' => 'session.'], function () {
        Route::get('/', 'InstanceSessionHistoryController@index')->name('index');
        Route::post('/', 'BotInstanceController@storeBotIdInSession')->name('create');
    });
});

// Admin routes
Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'admin'], 'namespace' => 'Admin', 'as' => 'admin.'], function () {
    Route::group(['prefix' => 'bots', 'as' => 'bots.'], function () {
        Route::get('/running', 'BotInstanceController@index')->name('running');
        Route::put('/{id}/status', 'BotController@changeStatus')->name('update.status');
        Route::get('/all', 'BotController@getAll')->name('all');
        Route::get('/running/{userId}', 'BotInstanceController@index')->name('user.running');
        Route::put('/running/status', 'BotInstanceController@changeStatus')->name('running.update.status');
        Route::post('/running/dispatch', 'BotInstanceController@dispatchLaunchInstace')->name('running.dispatch');
        Route::get('/check', 'BotInstanceController@checkBotIdInQueue')->name('running.check');
    });

    Route::group(['prefix' => 'user', 'as' => 'user.'], function() {
        Route::get('/', 'UserController@index')->name('index');
        Route::put('/{id}/status', 'UserController@changeStatus')->name('update.status');
        Route::put('/credit', 'UserController@updateCredit')->name('update.credit');
        Route::put('/{id}/timezone', 'UserController@updateTimezone')->name('update.timezone');
    });

    Route::group(['prefix' => 'session', 'as' => 'session.'], function () {
        Route::get('/', 'InstanceSessionHistoryController@index')->name('index');
        Route::post('/', 'BotInstanceController@storeBotIdInSession')->name('create');
    });

    Route::group(['prefix' => 'subscription', 'as' => 'subscription.'], function () {
        Route::put('/{id}/status', 'SubscriptionController@changeStatus')->name('update.status');
    });

    Route::resource('notification', 'NotificationController');
    Route::resource('subscription', 'SubscriptionController');
    Route::resource('bots', 'BotController');
});

Route::get('/user/credits', 'AppController@CalUserCreditScore')->name('CreditScoreEmail');
Route::get('/user/activation/{token}', 'AppController@UserActivation')->name('user-activation');

// Stripe routes
Route::post('/stripe/webhook', 'WebhookController@handleWebhook');
Route::get('/stripe-payment', 'StripeController@SendPayment')->name('stripe-payment');
