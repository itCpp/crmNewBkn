<?php

/*
|--------------------------------------------------------------------------
| API personal testing
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;

return Route::group(['prefix' => "testing"], function () {

    /** Начало тестирования */
    Route::post('start', 'Testing\Testings')->name('api.testing.start');
});
