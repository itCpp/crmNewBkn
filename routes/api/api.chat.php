<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Chat Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => "chat", 'middleware' => "user.old.token"], function () {

    /** Загрузка данных чата */
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

Route::group(['prefix' => "users/chat", 'middleware' => "user.token"], function () {

    /** Загрузка данных чата */
    Route::post('/', 'Chats\ForNewCrm\Chats@start')->name('api.users.chat');

    /** Загрузка данных выбранного чата */
    Route::post('room', 'Chats\ForNewCrm\Chats@room')->name('api.users.chat.room');

    /** Отправка сообщения */
    Route::post('sendMessage', 'Chats\ForNewCrm\Chats@sendMessage')->name('api.users.chat.sendMessage');
});
