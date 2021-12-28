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
    Route::post('startChat', 'Chats\Chat@startChat')->name('api.chat.startChat');
    /** Поиск сотрудника или чат группы */
    Route::post('searchRoom', 'Chats\Chat@searchRoom')->name('api.chat.searchRoom');
    /** Данные выбранной чат группы */
    Route::post('getRoom', 'Chats\Rooms@getRoom')->name('api.chat.getRoom');
    /** Вывод сообщений в группе */
    Route::post('getMessages', 'Chats\Chat@getMessages')->name('api.chat.getMessages');
    /** Отправка сообщений */
    Route::post('sendMessage', 'Chats\Chat@sendMessage')->name('api.chat.sendMessage');

    /** Выдача файла */
    Route::get('file/{hash?}', 'Chats\Chat@file')->name('api.chat.file.hash');
});
