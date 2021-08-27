<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

/** Првоерка типа авторизации */
Route::post('loginStart', 'Users\Auth@loginStart');
/** Завершение авторизации */
Route::post('login', 'Users\Auth@login');

/** Группа маршрутов авторизованного пользователя */
Route::group(['middleware' => 'user.token'], function() {

    /** Первоначальная загрузка страницы со всеми данными */
    Route::post('check', 'Users\Users@check');

    /** Маршрутизация админпанели */
    Route::group(['prefix' => "admin", 'middleware' => "user.can:admin_access"], function() {

        /** Первоначальная загрузка страницы со всеми данными */
        Route::post('start', 'Users\Users@adminCheck');

    });

    /** Маршрутизация админпанели разработчика */
    Route::group(['prefix' => "dev", 'middleware' => "user.can:block_dev"], function() {

        Route::group(['middleware' => "user.can:dev_permits"], function() {

            /** Первоначальная загрузка страницы со всеми данными */
            Route::post('getAllPermits', 'Dev\Permissions@getAllPermits');
            /** Создание нового или изменение старого правила */
            Route::post('savePermit', 'Dev\Permissions@savePermit');
            /** Вывод данных одного правила */
            Route::post('getPermit', 'Dev\Permissions@getPermit');

        });

    });

});
