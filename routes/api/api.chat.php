<?php

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
    /** Вывод сообщений в группе */
    Route::post('getMessages', 'Chats\Chat@getMessages');
    /** Отправка сообщений */
    Route::post('sendMessage', 'Chats\Chat@sendMessage');
});
