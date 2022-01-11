<?php

/*
|--------------------------------------------------------------------------
| API personal testing
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;

return Route::group(['prefix' => "testing"], function () {

    /** Загрузка данных тестирования */
    Route::post('get', 'Testing\Testings')->name('api.testing.get');

    /** Начало тестирования */
    Route::post('start', 'Testing\Testings@start')->name('api.testing.start');
});
