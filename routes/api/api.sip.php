<?php

use Illuminate\Support\Facades\Route;

return Route::group(['prefix' => "sip"], function () {

    /** Вывод статистики по внутренним звонкам */
    Route::post('stats', 'Sip\SipMain@stats')->name('api.admin.sip.stats');;
});
