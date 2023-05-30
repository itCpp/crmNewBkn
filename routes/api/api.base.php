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

    /** Устанавливает статус прихода для заявки */
    Route::get('record/coming', 'Base\Records@setComing');

    /** Вывод рейтинга колл-центра */
    Route::get('rating/callcenter', 'Base\Ratings@callcenter');

    /** Выводит список аудиозаписей по заявке */
    Route::get('calls', 'Base\Calls@get');

    /** Заключение договоров */
    Route::group(['prefix' => "upp"], function () {

        /** Выводит данные с комментариями для карточки клиента */
        Route::get('getCollComment', 'Base\UppAgreements@getCollComment');

        /** Устанавливает подтвержение комментария оператора */
        Route::get('setConfirmed', 'Base\UppAgreements@setConfirmed');
    });

    /** Привязвает идентификатор телеграма */
    Route::post('users/telegram/bind', 'Users\Settings@telegramBind');
});