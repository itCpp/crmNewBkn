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

    /** Следующий вопрос */
    Route::post('next', 'Testing\Testings@next')->name('api.testing.next');

    /** Создание теста */
    Route::post('create', 'Testing\Testings@create')->name('api.testing.create');
});
