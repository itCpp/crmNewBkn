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
Route::any('events/{type?}', 'Requests\Events@incoming')->name('api.events.type');

/** Добавление заявки */
// Route::post('addRequest', 'Requests\AddRequest@add');

/** Првоерка типа авторизации */
Route::post('loginStart', 'Users\Auth@loginStart')->name('api.loginStart');
/** Завершение авторизации */
Route::post('login', 'Users\Auth@login')->name('api.login');
/** Отмена запроса на авторизацию */
Route::post('loginCancel', 'Users\Auth@loginCancel')->name('api.loginCancel');

/** Получить номер телефона через идентификатор */
Route::any('getNumberFromId', 'Asterisk\Phones@getNumberFromId')->name('api.getNumberFromId');

include __DIR__ . "/api/api.free.php";

/** Маршрутизация внутреннего чата */
include __DIR__ . "/api/api.chat.php";

/** Группа маршрутов авторизованного пользователя */
Route::group(['middleware' => 'user.token'], function () {

    /** Выход из системы */
    Route::post('logout', 'Users\Auth@logout')->name('api.logout');

    Route::any('echo/auth', function (Request $request) {
        return \Illuminate\Support\Facades\Broadcast::auth($request);
    })->name('api.echo.auth');

    /** Первоначальная загрузка страницы со всеми данными */
    Route::post('check', 'Users\Users@check')->name('api.check');

    /** Маршрутизация по заявкам */
    Route::group([
        'prefix' => "requests",
        'middleware' => "user.can:requests_access",
    ], function () {

        /** Запуск заявок */
        Route::post('start', 'Requests\RequestStart@start')->name('api.requests.start');

        /** Вывод заявок */
        Route::post('get', 'Requests\Requests@get')->name('api.requests.get');
        /** Вывод одной строки */
        Route::post('getRow', 'Requests\Requests@getRow')->name('api.requests.getRow');
        Route::post('getRowForTab', 'Requests\Requests@getRowForTab')->name('api.requests.getRowForTab');

        /** Изменение данных заявки */
        Route::post('save', 'Requests\RequestChange@save')->name('api.requests.save');
        /** Изменение данных заявки отдельно в ячейке */
        Route::post('saveCell', 'Requests\RequestChange@saveCell')->name('api.requests.saveCell');

        /** Вывод списка доступных операторов для назначения на заявку */
        Route::post('changePinShow', 'Requests\RequestPins@changePinShow')->name('api.requests.changePinShow');
        /** Выдача заявки оператору */
        Route::post('setPin', 'Requests\RequestPins@setPin')->name('api.requests.setPin');

        /** Вывод списка секторов для выдачи заявки в нужный сектор */
        Route::post('changeSectorShow', 'Requests\RequestSectors@changeSectorShow')->name('api.requests.changeSectorShow');
        /** Передача заявки сектору */
        Route::post('setSector', 'Requests\RequestSectors@setSector')->name('api.requests.setSector');

        /** Вывод данных для создания нвой заявки вручную */
        Route::post('addShow', 'Requests\RequestAddManual@addShow')->middleware('user.can:request_add')->name('api.requests.addShow');
        /** Создание новой заявки вручную */
        Route::post('create', 'Requests\RequestAddManual@create')->middleware('user.can:request_add')->name('api.requests.create');

        /** Создание комментария для заявки */
        Route::post('sendComment', 'Requests\Comments@sendComment')->name('api.requests.sendComment');

        /** Счетчик заявок */
        Route::post('getCounter', 'Requests\Counters@getCounter')->middleware('permits.requests')->name('api.requests.getCounter');

        /** Добавление номера телефона в заявку */
        Route::post('addClientPhone', 'Requests\Clients@addClientPhone')->name('api.requests.addClientPhone');

        /** Вывод данных для отправки смс */
        Route::post('getSmsData', 'Callcenter\Sms@getSmsData')->name('api.requests.getSmsData');
        /** Отправка смс сообщения */
        Route::post('sendSms', 'Callcenter\Sms@sendSms')->middleware('user.can:requests_send_sms')->name('api.requests.sendSms');
        /** Проверка обновлений в сообщениях */
        Route::post('getSmsUpdates', 'Callcenter\Sms@getSmsUpdates')->name('api.requests.getSmsUpdates');
    });

    /** Маршрутизация различных рейтингов */
    Route::group(['prefix' => 'ratings'], function () {

        /** Вывод основного рейтинга колл-центров */
        Route::post('callcenter', 'Ratings\Ratings@getCallCenters')->name('api.ratings.callcenter');

        /** Данные по операторам */
        Route::post('getOperators', 'Statistics\Operators@getOperators')->name('api.ratings.getOperators');
    });

    /** Маршрутизация очередей */
    Route::group(['prefix' => 'queues', 'middleware' => 'user.can:queues_access'], function () {

        /** Вывод очереди */
        Route::post('getQueues', 'Queues\Queues@getQueues')->name('api.queues.getQueues');
        /** Решение по очереди */
        Route::post('done', 'Queues\Queues@done')->name('api.queues.done');
    });

    /** Работа с СМС-сками */
    Route::group(['prefix' => 'sms', 'middleware' => 'user.can:sms_access'], function () {

        /** Вывод сообщений */
        Route::post('get', 'Sms\Sms@get')->name('api.sms.get');
    });

    /** Работа со вторичными звонками */
    Route::group(['prefix' => 'secondcalls', 'middleware' => 'user.can:second_calls_access'], function () {

        /** Вывод звонков */
        Route::post('get', 'SecondCalls\SecondCalls@get')->name('api.secondcalls.get');
    });

    /** Маршрутизация для работы с учетными записями */
    Route::group(['prefix' => 'users'], function () {

        /** Вывод списка запросов авторизации */
        Route::post('authQueries', 'Users\Auth@authQueries')->middleware('user.can:user_auth_query')->name('api.users.authQueries');
        /** Завершение запроса авторизации */
        Route::post('authComplete', 'Users\Auth@complete')->middleware('user.can:user_auth_query')->name('api.users.authComplete');

        /** Ручная установка статуса сотрудника */
        Route::post('setWorkTime', 'Users\Worktime@setWorkTime')->name('api.users.setWorkTime');

        /** Вывод данных сотрудника */
        Route::post('getUserMainData', 'Users\UserMainData@getUserMainData')->name('api.users.getUserMainData');
    });

    /** Маршрутизация админпанели */
    Route::group(['prefix' => "admin", 'middleware' => "user.can:admin_access"], function () {

        /** Первоначальная загрузка страницы со всеми данными */
        Route::post('start', 'Admin\Admin@start')->name('api.admin.start');

        /** Вывод сотрудников для админки */
        Route::post('getUsers', 'Users\AdminUsers@getUsers')->name('api.admin.getUsers');
        /** Данные для вывода окна создания сотрудника */
        Route::post('getAddUserData', 'Users\AdminUsers@getAddUserData')->name('api.admin.getAddUserData');
        /** Данные для смены колл-центра сотрудника */
        Route::post('getCallCenterData', 'Users\AdminUsers@getCallCenterData')->name('api.admin.getCallCenterData');
        /** Создание или обновление данных сотрудника */
        Route::post('saveUser', 'Users\AdminUsers@saveUser')->name('api.admin.saveUser');
        /** Блокировка пользователя */
        Route::post('blockUser', 'Users\AdminUsers@blockUser')->name('api.admin.blockUser');
        /** Вывод ролей и разрешений для сотрудника */
        Route::post('getRolesAndPermits', 'Users\AdminUsers@getRolesAndPermits')->name('api.admin.getRolesAndPermits');
        /** Установка роли пользователю */
        Route::post('setUserRole', 'Users\AdminUsers@setUserRole')->middleware('user.can:admin_user_set_role')->name('api.admin.setUserRole');
        /** Установка разрешения пользователю */
        Route::post('setUserPermission', 'Users\AdminUsers@setUserPermission')->middleware('user.can:admin_user_set_permission')->name('api.admin.setUserPermission');

        /** Вывод списка колл-центорв */
        Route::post('getCallcenters', 'Callcenter\Callcenters@getCallcenters')->middleware('user.can:admin_callcenters')->name('api.admin.getCallcenters');
        /** Вывод списка секторов */
        Route::post('getCallcenterSectors', 'Callcenter\Callcenters@getCallcenterSectors')->middleware('user.can:admin_callcenters')->name('api.admin.getCallcenterSectors');
        /** Данные одного колл-центра */
        Route::post('getCallcenter', 'Callcenter\Callcenters@getCallcenter')->middleware('user.can:admin_callcenters')->name('api.admin.getCallcenter');
        /** Сохранение данных колл-центра */
        Route::post('saveCallcenter', 'Callcenter\Callcenters@saveCallcenter')->middleware('user.can:admin_callcenters')->name('api.admin.saveCallcenter');

        /** Данные одного сектора */
        Route::post('getSector', 'Callcenter\Sectors@getSector')->middleware('user.can:admin_callcenters')->name('api.admin.getSector');
        /** Изменение данных сектора */
        Route::post('saveSector', 'Callcenter\Sectors@saveSector')->middleware('user.can:admin_callcenters')->name('api.admin.saveSector');

        include __DIR__ . "/api/api.sip.php";

        Route::group(['middleware' => 'user.can:admin_callsqueue'], function () {

            /** Вывод настройки распределения звонков */
            Route::post('getDistributionCalls', 'Admin\DistributionCalls@getDistributionCalls')->name('api.admin.getDistributionCalls');
            /** Определние настроек единичного выбора */
            Route::post('distributionSetOnly', 'Admin\DistributionCalls@distributionSetOnly')->name('api.admin.distributionSetOnly');
            /** Сохранение значений распределения звонков */
            Route::post('distributionSetCountQueue', 'Admin\DistributionCalls@distributionSetCountQueue')->name('api.admin.distributionSetCountQueue');
            /** Включение сектора в распределение звонков */
            Route::post('setSectorDistribution', 'Admin\DistributionCalls@setSectorDistribution')->name('api.admin.setSectorDistribution');
        });
    });

    /** Маршрутизация админпанели разработчика */
    Route::group(['prefix' => "dev", 'middleware' => "user.can:block_dev"], function () {

        /** Вывод всех маршрутов */
        Route::post('getRoutes', 'Dev\Routes@getRoutes')->name('api.dev.getRoutes');

        /** Настройка разрешений */
        Route::group(['middleware' => "user.can:dev_permits"], function () {

            /** Первоначальная загрузка страницы со всеми данными */
            Route::post('getAllPermits', 'Dev\Permissions@getAllPermits')->name('api.dev.getAllPermits');
            /** Создание нового или изменение старого правила */
            Route::post('savePermit', 'Dev\Permissions@savePermit')->name('api.dev.savePermit');
            /** Вывод данных одного правила */
            Route::post('getPermit', 'Dev\Permissions@getPermit')->name('api.dev.getPermit');
        });

        /** Настройка ролей */
        Route::group(['middleware' => "user.can:dev_roles"], function () {

            /** Первоначальная загрузка страницы со всеми данными */
            Route::post('getAllRoles', 'Dev\Roles@getAllRoles')->name('api.dev.getAllRoles');
            /** Вывод разрешений роли */
            Route::post('getPermits', 'Dev\Roles@getPermits')->name('api.dev.getPermits');
            /** Установка права роли */
            Route::post('setRolePermit', 'Dev\Roles@setRolePermit')->name('api.dev.setRolePermit');

            /** Вывод данных одной роли */
            Route::post('getRole', 'Dev\Roles@getRole')->name('api.dev.getRole');
            /** Сохранение данных роли */
            Route::post('saveRole', 'Dev\Roles@saveRole')->name('api.dev.saveRole');

            /** Присвоение роли доступа к вкладке */
            Route::post('setTabForRole', 'Dev\Roles@setTabForRole')->name('api.dev.setTabForRole');
            /** Присвоение роли доступа к вкладке */
            Route::post('setStatusForRole', 'Dev\Roles@setStatusForRole')->name('api.dev.setStatusForRole');
        });

        /** Настройка источников и ресурсов */
        Route::group(['middleware' => "user.can:dev_sources"], function () {

            /** Список источников с ресурсами */
            Route::post('getSources', 'Dev\Sources@getSources')->name('api.dev.getSources');
            /** Создание нового источника */
            Route::post('createSource', 'Dev\Sources@createSource')->name('api.dev.createSource');

            /** Настройка источника */
            Route::post('getSourceData', 'Dev\Sources@getSourceData')->name('api.dev.getSourceData');
            /** Изменение настроек источника */
            Route::post('saveSourceData', 'Dev\Sources@saveSourceData')->name('api.dev.saveSourceData');

            /** Список ресурсов для источников */
            Route::post('getResources', 'Dev\Sources@getResources')->name('api.dev.getResources');
            /** Создание нового ресурса для источников */
            Route::post('createResource', 'Dev\Sources@createResource')->name('api.dev.createResource');

            /** Применение ресурса к источнику */
            Route::post('setResourceToSource', 'Dev\Sources@setResourceToSource')->name('api.dev.setResourceToSource');

            /** Вывод свободных и активных ресурсов по источнику */
            Route::post('getFreeResources', 'Dev\Sources@getFreeResources')->name('api.dev.getFreeResources');
        });

        /** Настройка статусов */
        Route::group(['middleware' => "user.can:dev_statuses"], function () {

            /** Список всех статусов */
            Route::post('getStatuses', 'Dev\Statuses@getStatuses')->name('api.dev.getStatuses');
            /** Создание нового статуса */
            Route::post('createStatus', 'Dev\Statuses@createStatus')->name('api.dev.createStatus');

            /** Вывод данных одного статуса */
            Route::post('getStatusData', 'Dev\Statuses@getStatusData')->name('api.dev.getStatusData');
            /** Изменение данных статуса */
            Route::post('saveStatus', 'Dev\Statuses@saveStatus')->name('api.dev.saveStatus');
            /** Изменение темы оформления вкладки */
            Route::post('setStatuseTheme', 'Dev\Statuses@setStatuseTheme')->name('api.dev.setStatuseTheme');
        });

        /** Настройка вкладок */
        Route::group(['middleware' => "user.can:dev_tabs"], function () {

            /** Список всех статусов */
            Route::post('getTabs', 'Dev\Tabs@getTabs')->name('api.dev.getTabs');
            /** Создание новой вкладки */
            Route::post('createTab', 'Dev\Tabs@createTab')->name('api.dev.createTab');

            /** Вывод данных одной вкладки */
            Route::post('getTab', 'Dev\Tabs@getTab')->name('api.dev.getTab');
            /** Изменение данных вкладки */
            Route::post('saveTab', 'Dev\Tabs@saveTab')->name('api.dev.saveTab');
            /** Вывод сформированного запроса */
            Route::post('getSql', 'Dev\Tabs@getSql')->name('api.dev.getSql');

            /** Вывод списка значений для конструктора запросов */
            Route::post('getListWhereIn', 'Dev\Tabs@getListWhereIn')->name('api.dev.getListWhereIn');

            /** Установка порядка вывода вкладок */
            Route::post('tabsPosition', 'Dev\Tabs@tabsPosition')->name('api.dev.tabsPosition');

            /** Применение статусов для вывода во вкладке */
            Route::post('setTabStatus', 'Dev\Tabs@setTabStatus')->name('api.dev.setTabStatus');
        });

        /** Журнал вызовов и настройка источников */
        Route::group(['middleware' => "user.can:dev_calls"], function () {

            /** Загрузка страницы журнала звонков */
            Route::post('getCalls', 'Admin\Calls@start')->name('api.dev.getCalls');
            /** Вывод списка слушателей входящих звонков */
            Route::post('getIncomingCallExtensions', 'Admin\Calls@getIncomingCallExtensions')->name('api.dev.getIncomingCallExtensions');
            /** Вывод данных одного слушателя */
            Route::post('getIncomingCallExtension', 'Admin\Calls@getIncomingCallExtension')->name('api.dev.getIncomingCallExtension');
            /** Сохранение данных или создание нового слушателя */
            Route::post('saveIncpmingExtension', 'Admin\Calls@saveIncpmingExtension')->name('api.dev.saveIncpmingExtension');

            /** Повторный запрос обработки входящего звонка */
            Route::post('retryIncomingCall', 'Admin\Calls@retryIncomingCall')->name('api.dev.retryIncomingCall');
        });

        /** Настройки офисов */
        Route::group(['middleware' => "user.can:dev_offices"], function () {

            /** Вывод списка офисов */
            Route::post('getOffices', 'Offices\Offices@getOffices')->name('api.dev.getOffices');
            /** Вывод данных офиса */
            Route::post('getOffice', 'Offices\Offices@getOffice')->name('api.dev.getOffice');
            /** Сохранение данных офиса */
            Route::post('saveOffice', 'Offices\Offices@saveOffice')->name('api.dev.saveOffice');
        });

        /** Маршрутизация блокировок */
        Route::group(['prefix' => 'block'], function () {

            /** Вывод основного рейтинга колл-центров */
            Route::post('statistic', 'Admin\Blocks@statistic')->name('api.dev.block.statistic');
            /** Блокировка ip адреса */
            Route::post('setBlockIp', 'Admin\Blocks@setBlockIp')->name('api.dev.block.setBlockIp');
            /** Информация по IP-адресу */
            Route::post('ipInfo', 'Admin\Blocks@ipInfo')->name('api.dev.block.ipInfo');
            /** Вывод заблокированных адресов */
            Route::post('getBlockData', 'Admin\Blocks@getBlockData')->name('api.dev.block.getBlockData');

            /** Вывод данных о просмотрах */
            Route::post('getViews', 'Admin\Blocks@getViews')->name('api.dev.block.getViews');
        });
    });
});
