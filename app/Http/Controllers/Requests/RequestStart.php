<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RequestStart extends Controller
{

    /**
     * Список проверки разрешений для заявки
     * 
     * @var array
     */
    public static $permitsList = [
        'requests_add', # Может добавлять новую заявку
        'requests_edit', # Может редактировать заявку
        'requests_pin_set', # Может устанавливать оператора на заявку
        'requests_pin_change', # Может менять оператора на заявке
        'requests_set_null_status', # Может устанавливать заявке статус "Не обработано"
        'requests_sector_set', # Может назначать заявку для сектора
        'requests_sector_change', # Может менять сектор в заявке
        'requests_comment_first', # Может оставлять первичный комментарий
        'requests_all_my_sector', # Видит все заявки и операторов только своего сектора
        'requests_all_sectors', # Видит заявки и операторов всех колл-центров
        'requests_all_callcenters', # Видит заявки и операторов всех секторов своего колл-центра
        'clients_show_phone', # Может видеть номера телефонов клиента
        'requests_addr_change', # Может менять адрес записи
        'queues_access', # Доступ к очередям
        'sms_access', # Доступ к смс сообщениям
        'second_calls_access', # Доступ ко вторичным звонкам
    ];

    /**
     * Проверенный список разрешений
     * 
     * @var array|\App\Http\Controllers\Users\Permissions
     */
    public static $permits = [];
    
    /**
     * Подготовка данных для вывода страницы заявок
     * 
     * @param \Iluminate\Http\Request $request
     * @return response
     */
    public static function start(Request $request)
    {
        // Формирование кнопок в верхушке сайта
        $menu = [];

        // Список всех вкладок, доступных пользователю
        $request->tabs = $request->user()->getAllTabs();

        // Проверка прав пользователя
        $permits = $request->user()->getListPermits(self::$permitsList);

        // Преобразование данных для вывода
        $tabs = $request->tabs->map(function ($tab) {
            return [
                'id' => $tab->id,
                'name' => $tab->name,
                'name_title' => $tab->name_title,
            ];
        });

        return response()->json([
            'tabs' => $tabs,
            'permits' => $permits,
            'topMenu' => $menu,
            'intervalCounter' => self::getCounterUpdateInterval(),
            'counter' => Counters::getCounterData($request),
        ]);

    }

    /**
     * Вывод настройки временного периода для обновления счетчика
     * В конфигурационном .env файле необходимо объявить переменную
     * `COUNTER_UPDATE_INTERVAL` которая будет принимать значение
     * периода времени в секундах
     * - `NULL`, `0` или отсутствие значения будут отключать период проверки
     * 
     * @return int|null Период времени интервала проверки в миллисекудах
     */
    public static function getCounterUpdateInterval()
    {
        if (!$options = env('COUNTER_UPDATE_INTERVAL', null))
            return null;

        return $options * 1000;
    }

}
