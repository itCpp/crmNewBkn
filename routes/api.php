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

/** Маршрутизая общего доступа */
include __DIR__ . "/api/api.free.php";

/** Маршрутизация внутреннего чата */
include __DIR__ . "/api/api.chat.php";

/** Маршрутизация тестирования сотрудников */
include __DIR__ . "/api/api.testing.php";

/** Маршрутизация для информации по базам */
include __DIR__ . "/api/api.base.php";

/** Группа маршрутов авторизованного пользователя */
Route::group(['middleware' => 'user.token'], function () {

    /** Выход из системы */
    Route::post('logout', 'Users\Auth@logout')->name('api.logout');

    Route::any('echo/auth', function (Request $request) {
        return \Illuminate\Support\Facades\Broadcast::auth($request);
    })->name('api.echo.auth');

    /** Первоначальная загрузка страницы со всеми данными */
    Route::post('check', 'Users\Users@check')->name('api.check');

    /** Запуск ЦРМ */
    Route::post('crm/start', 'Crm\Start@start')->name('api.crm.start');
    Route::post('requests/start', 'Crm\Start@start')->name('api.requests.start');

    /** Маршрутизация по заявкам */
    Route::group([
        'prefix' => "requests",
        'middleware' => "user.can:requests_access",
    ], function () {

        /** Вывод заявок */
        Route::post('get', 'Requests\Requests@get')->name('api.requests.get');
        /** Вывод одной строки */
        Route::post('getRow', 'Requests\Requests@getRow')->name('api.requests.getRow');
        Route::post('getRowForTab', 'Requests\Requests@getRowForTab')->name('api.requests.getRowForTab');

        /** Изменение данных заявки */
        Route::post('save', 'Requests\RequestChange@save')->name('api.requests.save');
        /** Изменение данных заявки отдельно в ячейке */
        Route::post('saveCell', 'Requests\RequestChange@saveCell')->name('api.requests.saveCell');
        /** Скрытие заявки из поднятых */
        Route::post('hideUplift', 'Requests\RequestChange@hideUplift')->name('api.requests.hideUplift')->middleware('user.can:requests_hide_uplift_rows');

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
        Route::post('counter', 'Requests\Counters@getCounterPage')->middleware('permits.requests')->name('api.requests.counter');

        /** Добавление номера телефона в заявку */
        Route::post('addClientPhone', 'Requests\Clients@addClientPhone')->name('api.requests.addClientPhone');

        /** Вывод данных для отправки смс */
        Route::post('getSmsData', 'Callcenter\Sms@getSmsData')->name('api.requests.getSmsData');
        /** Отправка смс сообщения */
        Route::post('sendSms', 'Callcenter\Sms@sendSms')->middleware('user.can:requests_send_sms')->name('api.requests.sendSms');
        /** Проверка обновлений в сообщениях */
        Route::post('getSmsUpdates', 'Callcenter\Sms@getSmsUpdates')->name('api.requests.getSmsUpdates');
        /** Получает номер телефона смс в зависимости от прав доступа */
        Route::post('getSmsPhone', 'Callcenter\Sms@getSmsPhone')->name('api.requests.getSmsPhone');

        /** Список аудиозаписей */
        Route::post('calls', 'Crm\Calls');

        /** Данные для модального окна обращений по рекламе */
        Route::post('ad/get', 'Requests\Ad@get')->name('api.requests.ad.get')->middleware('user.can:requests_show_resource');

        /** Вывод истории изменения в заявке */
        Route::post('story', 'Requests\Stories@get')->name('api.requests.story');

        /** Выводит данные для окна поиска */
        Route::get('search/info', 'Requests\Search@info')->name('api.requests.search.info');
    });

    /** Маршрутизация различных рейтингов */
    Route::group(['prefix' => 'ratings', 'middleware' => 'user.can:rating_access'], function () {

        /** Загрузка настроек пользователя для рейтинга */
        Route::post('callcenter/start', 'Ratings\Ratings@ratingStart')->name('api.ratings.callcenter.start');
        /** Вывод основного рейтинга колл-центров */
        Route::post('callcenter', 'Ratings\Ratings@getCallCenters')
            ->name('api.ratings.callcenter')
            ->middleware(\App\Http\Middleware\WriteLastViewTimePart::class);

        /** Данные по операторам */
        Route::post('getOperators', 'Statistics\Operators@getOperators')->name('api.ratings.getOperators');
    });

    /** Маршрутизация очередей */
    Route::group(['prefix' => 'queues', 'middleware' => 'user.can:queues_access'], function () {

        /** Вывод очереди */
        Route::post('getQueues', 'Queues\Queues@getQueues')
            ->name('api.queues.getQueues')
            ->middleware(\App\Http\Middleware\WriteLastViewTimePart::class);
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

        /** Вывод данных для кабинета */
        Route::post('mydata', 'Users\UserMainData')->name('api.users.mydata');

        /** Вывод временной шкалы */
        Route::post('getWorktimeTape', 'Users\UserMainData@getTapeTimes')->name('api.users.getworktimetape');

        /** Выводит список пройденных тестирований */
        Route::post('mytests', 'Testing\MyTests@mytests')->name('api.users.mytests');

        /** Создание учетной записи нового сотрудника */
        Route::post('save', 'Users\AdminUsers@create')->middleware('user.can:user_create')->name('api.users.save');

        /** Вывод уведомления и установка даты чтения */
        Route::post('notifications/read', 'Users\Notifications@read')->name('api.users.notifications.read');

        /** Отмечает все уведомления пользователя как прочитанные */
        Route::post('notifications/read/all', 'Users\Notifications@readAll')->name('api.users.notifications.read.all');
    });

    /** Маршрутищация работы с договорными клиентами */
    Route::group([
        'prefix' => "agreements",
        'middleware' => 'user.can:clients_agreements_access'
    ], function () {

        /** Список договоров */
        Route::post('/', 'Agreements\Agreements@index')
            ->name('api.agreements')
            ->middleware(\App\Http\Middleware\WriteLastViewTimePart::class);
        /** Вывод данных одного договора для окна редактирования */
        Route::post('/get', 'Agreements\Agreements@get')->name('api.agreemetns.get');
        /** Сохраняет статус и комментарий по договору */
        Route::post('/save', 'Agreements\Agreements@save')->name('api.agreemetns.save');
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
        /** Выводит список активных сессий */
        Route::get('users/online', 'Users\Online@index')->name('api.admin.users.online');
        /** Завершает сессию пользователя */
        Route::delete('users/online/delete', 'Users\Online@destroy')->name('api.admin.users.online.delete');
        /** Выводит данный сессий одного пользователя */
        Route::get('users/online/get', 'Users\Online@get')->name('api.admin.users.online.delete.get');

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
        /** Сохраняет источник для автоматической установки сектора новой заявке */
        Route::post('setAutoSector', 'Callcenter\Sectors@setAutoSector')->middleware('user.can:admin_callcenters')->name('api.admin.saveSector');

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

    /** Управление штрафами */
    Route::group(['prefix' => 'fines'], function () {

        /** Вывод штрафов */
        Route::post('index', 'Fines\Fines@index')->middleware('user.can:user_fines_access');
        /** Вывод штрафа */
        Route::post('get', 'Fines\Fines@get');
        /** Добавление нового штрафа */
        Route::put('create', 'Fines\Fines@create')->middleware('user.can:user_fines_create');
        /** Удаление штрафа */
        Route::delete('delete', 'Fines\Fines@delete')->middleware('user.can:user_fines_delete');
        /** Восстановление удаленного штрафа */
        Route::post('restore', 'Fines\Fines@restore');
        /** Данные для создания штрафа по заявке */
        Route::post('request', 'Fines\RequestData@get')->middleware('user.can:user_fines_create');
        /** Поиск сотрудников для штрафа */
        Route::post('user/find', 'Fines\RequestData@find')->middleware('user.can:user_fines_create');
    });

    /** Маршрутизация админпанели разработчика */
    include __DIR__ . "/api/api.dev.php";

    /** Вывод внутренних телефонных номеров */
    Route::get('phoneboock', 'Crm\Phoneboock@get')->name('api.phoneboock');

    /** Маршрутизация журнала вызовов */
    Route::group(['prefix' => "calls", 'middleware' => "user.can:calls_log_access"], function () {

        /** Выводит список звонков */
        Route::post('log', 'Crm\Calls@getLog');

        /** Проверка скрытого номера телефона */
        Route::post('get', 'Crm\Calls@getLogRow');
    });
});
