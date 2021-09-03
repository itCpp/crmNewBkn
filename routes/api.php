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

        /** Вывод сотрудников для админки */
        Route::post('getUsers', 'Users\AdminUsers@getUsers');
        /** Данные для вывода окна создания сотрудника */
        Route::post('getAddUserData', 'Users\AdminUsers@getAddUserData');
        /** Данные для смены колл-центра сотрудника */
        Route::post('getCallCenterData', 'Users\AdminUsers@getCallCenterData');
        /** Создание или обновление данных сотрудника */
        Route::post('saveUser', 'Users\AdminUsers@saveUser');
        /** Блокировка пользователя */
        Route::post('blockUser', 'Users\AdminUsers@blockUser');
        /** Вывод ролей и разрешений для сотрудника */
        Route::post('getRolesAndPermits', 'Users\AdminUsers@getRolesAndPermits');
        /** Установка роли пользователю */
        Route::post('setUserRole', 'Users\AdminUsers@setUserRole')->middleware('user.can:admin_user_set_role');
        /** Установка разрешения пользователю */
        Route::post('setUserPermission', 'Users\AdminUsers@setUserPermission')->middleware('user.can:admin_user_set_permission');

        /** Вывод списка колл-центорв */
        Route::post('getCallcenters', 'Callcenter\Callcenters@getCallcenters')->middleware('user.can:admin_callcenters');
        /** Вывод списка секторов */
        Route::post('getCallcenterSectors', 'Callcenter\Callcenters@getCallcenterSectors')->middleware('user.can:admin_callcenters');
        /** Данные одного колл-центра */
        Route::post('getCallcenter', 'Callcenter\Callcenters@getCallcenter')->middleware('user.can:admin_callcenters');
        /** Сохранение данных колл-центра */
        Route::post('saveCallcenter', 'Callcenter\Callcenters@saveCallcenter')->middleware('user.can:admin_callcenters');
        
        
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

        Route::group(['middleware' => "user.can:dev_roles"], function() {

            /** Первоначальная загрузка страницы со всеми данными */
            Route::post('getAllRoles', 'Dev\Roles@getAllRoles');
            /** Вывод разрешений роли */
            Route::post('getPermits', 'Dev\Roles@getPermits');
            /** Установка права роли */
            Route::post('setRolePermit', 'Dev\Roles@setRolePermit');

            /** Вывод данных одной роли */
            Route::post('getRole', 'Dev\Roles@getRole');
            /** Сохранение данных роли */
            Route::post('saveRole', 'Dev\Roles@saveRole');

        });

    });

});
