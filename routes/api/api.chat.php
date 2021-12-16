<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| API Chat Routes
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;

return Route::group(['prefix' => "chat", 'middleware' => "user.old.token"], function () {

    /** Информация по IP-адресу */
    Route::post('startChat', 'Chats\Chat@startChat');
    /** Поиск сотрудника или чат группы */
    Route::post('searchRoom', 'Chats\Chat@searchRoom');
    /** Данные выбранной чат группы */
    Route::post('getRoom', 'Chats\Rooms@getRoom');
    /** Вывод сообщений в группе */
    Route::post('getMessages', 'Chats\Chat@getMessages');
    /** Отправка сообщений */
    Route::post('sendMessage', 'Chats\Chat@sendMessage');

    /** Выдача файла */
    Route::get('file/{hash?}', 'Chats\Chat@file');
});
