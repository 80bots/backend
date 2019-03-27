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
    return view('welcome');
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
            Route::post('change-status', 'UserInstancesController@changeStatus')->name('change-status');
        });
    });

    Route::resource('bots','BotsController');
    Route::group(['prefix' => 'bots', 'as' => 'bots.'], function() {
        Route::post('change-status', 'BotsController@ChangeStatus')->name('change-status');
        /*Route::get('/', 'BotsController@index')->name('index');
        Route::get('create', 'BotsController@create')->name('create');
        Route::post('store', 'BotsController@store')->name('store');
        Route::get('{id}/edit', 'BotsController@edit')->name('edit');
        Route::get('{id}/edit', 'BotsController@edit')->name('edit');*/
    });
});

Route::group(['prefix' => 'user', 'as' => 'user.', 'middleware' => ['auth', 'user']], function(){
    Route::get('dashboard', 'UserController@index')->name('dashboard');

    Route::group(['prefix' => 'instance', 'as' => 'instance.'], function() {
        Route::get('/', 'UserInstancesController@index')->name('index');
        Route::get('create', 'UserInstancesController@create')->name('create');
        Route::post('change-status', 'UserInstancesController@changeStatus')->name('change-status');
    });
});
