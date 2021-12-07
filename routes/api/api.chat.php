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
});
