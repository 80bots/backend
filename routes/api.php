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

    // User bots
    Route::group(['prefix' => 'bots', 'as' => 'bots.'], function () {
        Route::get('/', 'BotController@index')->name('index');
        Route::get('/running', 'BotInstanceController@index')->name('running');
//        Route::put('/running/status', 'BotInstanceController@changeStatus')->name('running.update.status');
//        Route::post('/running/dispatch', 'BotInstanceController@dispatchLaunchInstace')->name('running.dispatch');
//        Route::get('/check', 'BotInstanceController@checkBotIdInQueue')->name('running.check');
    });

});
