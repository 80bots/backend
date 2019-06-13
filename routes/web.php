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

Route::get('/', function () {
    return view('auth.login');
});


Auth::routes();

//Route::get('/home', 'HomeController@index')->name('home');
Route::get('/user-activation/{id}', 'AppController@UserActivation')->name('user-activation');

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['auth','admin'], 'namespace' => 'admin'], function(){
    Route::get('dashboard', 'UserController@index')->name('dashboard');

    Route::group(['prefix' => 'user', 'as' => 'user.'], function() {
        Route::get('/', 'UserController@index')->name('index');
        Route::post('change-status', 'UserController@changeStatus')->name('change-status');
        Route::post('update-credit', 'UserController@updateCredit')->name('update-credit');

        Route::group(['prefix' => 'instance', 'as' => 'instance.'], function() {
            Route::get('list/{id}', 'UserInstancesController@index')->name('list');
            Route::get('running', 'UserInstancesController@runningInstances')->name('running');
            Route::post('change-status', 'UserInstancesController@changeStatus')->name('change-status');
        });
    });

    Route::group(['prefix' => 'instance', 'as' => 'instance.'], function() {
        Route::get('running', 'UserInstancesController@runningInstances')->name('running');
    });

    Route::resource('bots','BotsController');
    Route::group(['prefix' => 'bots', 'as' => 'bots.'], function() {
        Route::post('change-status', 'BotsController@ChangeStatus')->name('change-status');
    });
});

Route::group(['prefix' => 'user', 'as' => 'user.', 'middleware' => ['auth', 'user']], function(){
    Route::get('dashboard', 'UserController@index')->name('dashboard');
    Route::get('profile/{id}', 'UserController@show')->name('profile');
    Route::get('cal-used-credit', 'AppController@CalUsedCredit')->name('cal-used-credit');
    Route::get('cal-up-time', 'AppController@CalInstancesUpTime')->name('cal-up-time');

    Route::get('cron-start-scheduling','AppController@startScheduling')->name('cron-start-scheduling');
    Route::get('cron-stop-scheduling','AppController@stopScheduling')->name('cron-stop-scheduling');

    Route::get('cron-scheduling','AppController@Scheduling')->name('cron-scheduling');


    Route::get('bots-list', 'UserInstancesController@BotList')->name('bots.list');
    Route::get('bots-all-list/{id}', 'UserInstancesController@BotAllList')->name('bots.all.list');

    Route::resource('scheduling', 'SchedulingInstancesController');

    Route::group(['prefix' => 'scheduling', 'as' => 'scheduling.'], function() {
        Route::get('check-scheduled/{id}', 'SchedulingInstancesController@CheckScheduled')->name('check-scheduled');
        Route::post('change-status', 'SchedulingInstancesController@changeStatus')->name('change-status');
    });

    Route::resource('instance','UserInstancesController');
    Route::group(['prefix' => 'instance', 'as' => 'instance.'], function() {
        Route::post('change-status', 'UserInstancesController@changeStatus')->name('change-status');
    });
});
