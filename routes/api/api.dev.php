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

    /** Маршрутизация настроек */
    Route::group(['prefix' => "settings"], function () {

        /** Вывод глобальных настроек */
        Route::post('/', 'Admin\Settings@index');
        /** Применение настройки */
        Route::post('set', 'Admin\Settings@set');
    });

    /** Маршрутизация настроек баз данных сайтов */
    Route::group(['prefix' => "databases"], function () {

        /** Вывод всех баз данных */
        Route::post('/', 'Admin\Databases@index');
        /** Вывод данных одной строки */
        Route::post('get', 'Admin\Databases@get');
        /** Применение настройки */
        Route::post('set', 'Admin\Databases@set');
        /** Миграция базы данных сайта */
        Route::post('migrate', 'Admin\DataBases\Migrations@migrate');

        /** Список сайтов с индивидуальной статистикой */
        Route::post('sites', 'Admin\Databases@sites');
    });

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
        /** Назначение роли источник заявок */
        Route::post('role/setSource', 'Dev\Roles@setSource')->name('api.dev.role.setSource');
    });

    /** Настройка источников и ресурсов */
    Route::group(['middleware' => "user.can:dev_sources"], function () {

        /** Список источников с ресурсами */
        Route::post('getSources', 'Dev\Sources@getSources')->name('api.dev.getSources');
        /** Создание нового источника */
        Route::post('createSource', 'Dev\Sources@createSource')->name('api.dev.createSource');

        /** Вывод сайтов из источников */
        Route::get('resource/sites', 'Dev\Sources@sites')->name('api.dev.resource.sites');
        /** Проверка сайта */
        Route::post('resource/site', 'Dev\Sources@site')->name('api.dev.resource.site');
        /** Включение в список проверки */
        Route::post('resource/site/check', 'Dev\Sources@siteCheck')->name('api.dev.resource.site.check');

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
        Route::post('tabs/get', 'Dev\Tabs@getTab')->name('api.dev.tabs.get');
        /** Изменение данных вкладки */
        Route::post('saveTab', 'Dev\Tabs@saveTab')->name('api.dev.saveTab');
        Route::post('tabs/save', 'Dev\Tabs@saveTab')->name('api.dev.tabs.save');
        /** Вывод сформированного запроса */
        // Route::post('getSql', 'Dev\Tabs@getSql')->name('api.dev.getSql');
        Route::post('tabs/sql', 'Dev\Tabs@getSql')->name('api.dev.tabs.sql');

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

        /** Вывод внутренних номеров */
        Route::post('calls/extensions', 'Admin\Calls@extensions')->name('api.dev.calls.extensions');
        /** Вывод одного внутреннего номера */
        Route::post('calls/extension', 'Admin\Calls@extension')->name('api.dev.calls.extension');
        /** Вывод одного внутреннего номера */
        Route::post('calls/extension/save', 'Admin\Calls@save')->name('api.dev.calls.extension.save');
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

        /** Вывод статистики по сайтам из индивидуальных баз */
        Route::post('allstatistics', 'Admin\Blocks@allstatistics')->name('api.dev.block.allstatistics');
        /** Создание нового фильтра по utm */
        Route::put('allstatistics/setutm', 'Admin\Blocks@setutm')->name('api.dev.block.setutm');
        /** Вывод списка фильтра по utm для сайта */
        Route::post('allstatistics/getutm', 'Admin\Blocks@getutm')->name('api.dev.block.getutm');
        /** Удаляет строку фильтра */
        Route::delete('allstatistics/droputm', 'Admin\Blocks@droputm')->name('api.dev.block.droputm');
        /** Скрывает или отображает ip для вывода в таблице */
        Route::post('allstatistics/setHideIp', 'Admin\Blocks@setHideIp')->name('api.dev.block.setHideIp');
        /** Вывод информации об IP для блокировки по сайтам */
        Route::post('getip', 'Admin\Blocks\OwnStatistics@getip');
        /** Блокировка ip на сайте */
        Route::post('site/setblockip', 'Admin\Blocks\OwnStatistics@setBlockIp');
        /** Временная блокировка ip на сайте */
        Route::post('site/setautoblockip', 'Admin\Blocks\OwnStatistics@setAutoBlockIp');
        /** Блокировка всех адресов */
        Route::post('site/setblockipall', 'Admin\BlocksDrive\BlockIps@setAll');
        /** Блокировка на всех сайтах одновременно */
        Route::put('site/setAllBlockIp', 'Admin\Blocks\OwnStatistics@setAllBlockIp');
        /** Исключения хостов для сайта */
        Route::post('exceptionshostsite', 'Admin\SitesExceptions@get');

        /** Вывод информации о блокировки по хосту */
        Route::post('gethost', 'Admin\Blocks\OwnStatistics@gethost');
        /** Изменение имени хоста */
        Route::put('sethost', 'Admin\Blocks\OwnStatistics@sethost');
        /** Блокировка хоста на сайте */
        Route::post('site/setblockhost', 'Admin\Blocks\OwnStatistics@setBlockHost');

        /** Вывод данных о просмотрах */
        Route::post('getViews', 'Admin\Blocks@getViews')->name('api.dev.block.getViews');

        /** Вывод список сайтов для статистики по сайтам */
        Route::post('sites', 'Admin\Sites@sites')->name('api.dev.block.sites');
        /** Вывод статистики по сайтам */
        Route::post('sitesStats', 'Admin\Sites@sitesStats')->name('api.dev.block.sitesStats');
        /** Вывод данных графика */
        Route::post('getChartSite', 'Admin\Sites@getChartSite')->name('api.dev.block.getChartSite');
        Route::post('getChartSiteOwnStat', 'Admin\Sites@getChartSiteOwnStat')->name('api.dev.block.getChartSiteOwnStat');

        /** Вывод информации об IP для блокировки по ID */
        Route::post('ip', 'Admin\Blocks\ClientId@ip')->name('api.dev.block.ip');
        /** Сохранение информации об IP для блокировки по ID */
        Route::post('ip/save', 'Admin\Blocks\ClientId@ipSave')->name('api.dev.block.ip.save');

        /** Вывод заблокированных IP */
        Route::post('drive/ip', 'Admin\BlocksDrive\BlockIps@index')->name('api.dev.block.drive.ip');
        /** Вывод заблокированных Хостов */
        Route::post('drive/host', 'Admin\BlocksDrive\BlockHosts@index')->name('api.dev.block.drive.host');

        /** Создание блокировки */
        Route::post('create', 'Admin\BlocksDrive\Create@create');

        /** Комментарий по IP */
        Route::post('commentIp', 'Admin\Blocks\IpInfos@commentIp');
        Route::post('setCommentIp', 'Admin\Blocks\IpInfos@setCommentIp');
    });

    /** Маршрутизация шлюзов */
    Route::group(['prefix' => 'gates'], function () {

        /** Вывод шлюзов */
        Route::post('/', 'Admin\Gates@index')->name('api.dev.gates');
        /** Вывод шлюза */
        Route::post('/get', 'Admin\Gates@get')->name('api.dev.gates.get');
        /** Сохранение шлюза */
        Route::post('/save', 'Admin\Gates@save')->name('api.dev.gates.save');
    });

    /** Маршрутизация работы с входящими событиями */
    Route::group(['prefix' => 'events'], function () {

        /** Вывод события */
        Route::get('get', 'Admin\Events@get');
        /** Вывод типов событий */
        Route::get('get/types', 'Admin\Events@types');
    });

    /** Маршрутизация логирований */
    Route::group(['prefix' => "logs"], function () {

        /** Вывод настроек логирования */
        Route::get('/', 'Admin\Logs@index');

        /** Вывод одной строки лога */
        Route::post('get', 'Admin\Logs@get');
    });
});
