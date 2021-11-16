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

/** Обработка входящего события */
Route::post('events', 'Requests\Events@incoming');

/** Добавление заявки */
// Route::post('addRequest', 'Requests\AddRequest@add');

/** Првоерка типа авторизации */
Route::post('loginStart', 'Users\Auth@loginStart');
/** Завершение авторизации */
Route::post('login', 'Users\Auth@login');
/** Отмена запроса на авторизацию */
Route::post('loginCancel', 'Users\Auth@loginCancel');

/** Группа маршрутов авторизованного пользователя */
Route::group(['middleware' => 'user.token'], function() {

    /** Выход из системы */
    Route::post('logout', 'Users\Auth@logout');

    Route::any('echo/auth', function (Request $request) {
        return \Illuminate\Support\Facades\Broadcast::auth($request);
    });

    /** Первоначальная загрузка страницы со всеми данными */
    Route::post('check', 'Users\Users@check');

    /** Маршрутизация по заявкам */
    Route::group([
        'prefix' => "requests",
        'middleware' => "user.can:requests_access",
    ], function() {

        /** Запуск заявок */
        Route::post('start', 'Requests\RequestStart@start');

        /** Вывод заявок */
        Route::post('get', 'Requests\Requests@get');
        /** Вывод одной строки */
        Route::post('getRow', 'Requests\Requests@getRow');
        Route::post('getRowForTab', 'Requests\Requests@getRowForTab');

        /** Изменение данных заявки */
        Route::post('save', 'Requests\RequestChange@save');
        /** Изменение данных заявки отдельно в ячейке */
        Route::post('saveCell', 'Requests\RequestChange@saveCell');

        /** Вывод списка доступных операторов для назначения на заявку */
        Route::post('changePinShow', 'Requests\RequestPins@changePinShow');
        /** Выдача заявки оператору */
        Route::post('setPin', 'Requests\RequestPins@setPin');

        /** Вывод списка секторов для выдачи заявки в нужный сектор */
        Route::post('changeSectorShow', 'Requests\RequestSectors@changeSectorShow');
        /** Передача заявки сектору */
        Route::post('setSector', 'Requests\RequestSectors@setSector');

        /** Вывод данных для создания нвой заявки вручную */
        Route::post('addShow', 'Requests\RequestAddManual@addShow')->middleware('user.can:request_add');
        /** Создание новой заявки вручную */
        Route::post('create', 'Requests\RequestAddManual@create')->middleware('user.can:request_add');

        /** Создание комментария для заявки */
        Route::post('sendComment', 'Requests\Comments@sendComment');

        /** Счетчик заявок */
        Route::post('getCounter', 'Requests\Counters@getCounter')->middleware('permits.requests');

        /** Добавление номера телефона в заявку */
        Route::post('addClientPhone', 'Requests\Clients@addClientPhone');

    });

    /** Маршрутизация для работы с учетными записями */
    Route::group(['prefix' => 'users'], function() {

        /** Вывод списка запросов авторизации */
        Route::post('authQueries', 'Users\Auth@authQueries')->middleware('user.can:user_auth_query');
        /** Завершение запроса авторизации */
        Route::post('authComplete', 'Users\Auth@complete')->middleware('user.can:user_auth_query');

        /** Ручная установка статуса сотрудника */
        Route::post('setWorkTime', 'Users\Worktime@setWorkTime');

        /** Вывод данных сотрудника */
        Route::post('getUserMainData', 'Users\UserMainData@getUserMainData');

    });

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
        
        include __DIR__ . "/api/api.sip.php";

        Route::group(['middleware' => 'user.can:admin_callsqueue'], function() {
            /** Вывод настройки распределения звонков */
            Route::post('getDistributionCalls', 'Admin\DistributionCalls@getDistributionCalls');
            /** Определние настроек единичного выбора */
            Route::post('distributionSetOnly', 'Admin\DistributionCalls@distributionSetOnly');
            /** Сохранение значений распределения звонков */
            Route::post('distributionSetCountQueue', 'Admin\DistributionCalls@distributionSetCountQueue');
            /** Включение сектора в распределение звонков */
            Route::post('setSectorDistribution', 'Admin\DistributionCalls@setSectorDistribution');
        });

    });

    /** Маршрутизация админпанели разработчика */
    Route::group(['prefix' => "dev", 'middleware' => "user.can:block_dev"], function() {

        /** Настройка разрешений */
        Route::group(['middleware' => "user.can:dev_permits"], function() {

            /** Первоначальная загрузка страницы со всеми данными */
            Route::post('getAllPermits', 'Dev\Permissions@getAllPermits');
            /** Создание нового или изменение старого правила */
            Route::post('savePermit', 'Dev\Permissions@savePermit');
            /** Вывод данных одного правила */
            Route::post('getPermit', 'Dev\Permissions@getPermit');

        });

        /** Настройка ролей */
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

            /** Присвоение роли доступа к вкладке */
            Route::post('setTabForRole', 'Dev\Roles@setTabForRole');
            /** Присвоение роли доступа к вкладке */
            Route::post('setStatusForRole', 'Dev\Roles@setStatusForRole');

        });

        /** Настройка источников и ресурсов */
        Route::group(['middleware' => "user.can:dev_sources"], function() {

            /** Список источников с ресурсами */
            Route::post('getSources', 'Dev\Sources@getSources');
            /** Создание нового источника */
            Route::post('createSource', 'Dev\Sources@createSource');

            /** Настройка источника */
            Route::post('getSourceData', 'Dev\Sources@getSourceData');
            /** Изменение настроек источника */
            Route::post('saveSourceData', 'Dev\Sources@saveSourceData');

            /** Список ресурсов для источников */
            Route::post('getResources', 'Dev\Sources@getResources');
            /** Создание нового ресурса для источников */
            Route::post('createResource', 'Dev\Sources@createResource');

            /** Применение ресурса к источнику */
            Route::post('setResourceToSource', 'Dev\Sources@setResourceToSource');

            /** Вывод свободных и активных ресурсов по источнику */
            Route::post('getFreeResources', 'Dev\Sources@getFreeResources');

        });

        /** Настройка статусов */
        Route::group(['middleware' => "user.can:dev_statuses"], function() {

            /** Список всех статусов */
            Route::post('getStatuses', 'Dev\Statuses@getStatuses');
            /** Создание нового статуса */
            Route::post('createStatus', 'Dev\Statuses@createStatus');

            /** Вывод данных одного статуса */
            Route::post('getStatusData', 'Dev\Statuses@getStatusData');
            /** Изменение данных статуса */
            Route::post('saveStatus', 'Dev\Statuses@saveStatus');
            /** Изменение темы оформления вкладки */
            Route::post('setStatuseTheme', 'Dev\Statuses@setStatuseTheme');

        });

        /** Настройка вкладок */
        Route::group(['middleware' => "user.can:dev_tabs"], function() {

            /** Список всех статусов */
            Route::post('getTabs', 'Dev\Tabs@getTabs');
            /** Создание новой вкладки */
            Route::post('createTab', 'Dev\Tabs@createTab');

            /** Вывод данных одной вкладки */
            Route::post('getTab', 'Dev\Tabs@getTab');
            /** Изменение данных вкладки */
            Route::post('saveTab', 'Dev\Tabs@saveTab');
            /** Вывод сформированного запроса */
            Route::post('getSql', 'Dev\Tabs@getSql');

            /** Вывод списка значений для конструктора запросов */
            Route::post('getListWhereIn', 'Dev\Tabs@getListWhereIn');

            /** Установка порядка вывода вкладок */
            Route::post('tabsPosition', 'Dev\Tabs@tabsPosition');

            /** Применение статусов для вывода во вкладке */
            Route::post('setTabStatus', 'Dev\Tabs@setTabStatus');

        });

        /** Журнал вызовов и настройка источников */
        Route::group(['middleware' => "user.can:dev_calls"], function() {

            /** Загрузка страницы журнала звонков */
            Route::post('getCalls', 'Admin\Calls@start');
            /** Вывод списка слушателей входящих звонков */
            Route::post('getIncomingCallExtensions', 'Admin\Calls@getIncomingCallExtensions');
            /** Вывод данных одного слушателя */
            Route::post('getIncomingCallExtension', 'Admin\Calls@getIncomingCallExtension');
            /** Сохранение данных или создание нового слушателя */
            Route::post('saveIncpmingExtension', 'Admin\Calls@saveIncpmingExtension');

            /** Повторный запрос обработки входящего звонка */
            Route::post('retryIncomingCall', 'Admin\Calls@retryIncomingCall');

        });

    });

});
