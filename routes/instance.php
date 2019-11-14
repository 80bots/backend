<?php

use Illuminate\Support\Facades\Route;

// Special routes for the instance in order to avoid throttling error;
Route::group([
    'prefix' => 'instances',
    'as' => 'instances.',
    'namespace' => 'Common\Instances',
    'middleware' => ['auth:api']
], function () {
    Route::post('/{instance_id}/objects', 'FileSystemController@storeS3Object');
});
