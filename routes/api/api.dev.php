<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Маршрутизация админпанели разработчика
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => "dev", 'middleware' => "user.can:block_dev"], function () {

    /** Вывод всех маршрутов */
    Route::post('getRoutes', 'Dev\Routes')->name('api.dev.getRoutes');

    /** Вывод глобальных настроек */
    Route::post('settings', 'Admin\Settings@index');

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

        /** Вывод список сайтов для статистики по сайтам */
        Route::post('sites', 'Admin\Sites@sites')->name('api.dev.block.sites');
        /** Вывод статистики по сайтам */
        Route::post('sitesStats', 'Admin\Sites@sitesStats')->name('api.dev.block.sitesStats');
        /** Вывод данных графика */
        Route::post('getChartSite', 'Admin\Sites@getChartSite')->name('api.dev.block.getChartSite');
    });
});
