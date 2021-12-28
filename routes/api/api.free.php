<?php

/*
|--------------------------------------------------------------------------
| API FREE Routes
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;

return Route::group(['prefix' => "free"], function () {

    /** Информация по IP-адресу */
    Route::post('getIpInfo', 'Admin\Blocks@getIpInfo')->name('api.free.getIpInfo');
});
