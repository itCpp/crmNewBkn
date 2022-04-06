<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for BASE
|--------------------------------------------------------------------------
|
| Маршрутизация, предназначенная для вывода информации из ЦРМ в базы
|
*/

Route::group([
    'prefix' => "base",
    'middleware' => \App\Http\Middleware\CheckAccessServer::class
], function () {

    /** Вывод количества записей */
    Route::get('records', 'Base\Records@get');

    /** Вывод рейтинга колл-центра */
    Route::get('rating/callcenter', 'Base\Ratings@callcenter');
});