<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Infos\Themes;
use App\Http\Controllers\Requests\Counters;
use Illuminate\Http\Request;

class Start extends Controller
{
    /**
     * Список проверки разрешений для заявки
     * 
     * @var array
     */
    public static $permitsList = [
        'clients_agreements_access', # Доступ к договорным клиентам
        'clients_consultation_access', # Доступ к клиентам с бесплатной консультацией
        'clients_show_phone', # Может видеть номера телефонов клиента
        'queues_access', # Доступ к очередям
        'rating_access', # Доступ к рейтингу
        'requests_access', # Доступ к заявкам
        'requests_add', # Может добавлять новую заявку
        'requests_addr_change', # Может менять адрес записи
        'requests_all_callcenters', # Видит заявки и операторов всех секторов своего колл-центра
        'requests_all_my_sector', # Видит все заявки и операторов только своего сектора
        'requests_all_sectors', # Видит заявки и операторов всех колл-центров
        'requests_comment_first', # Может оставлять первичный комментарий
        'requests_edit', # Может редактировать заявку
        'requests_pin_change', # Может менять оператора на заявке
        'requests_pin_set', # Может устанавливать оператора на заявку
        'requests_sector_change', # Может менять сектор в заявке
        'requests_sector_set', # Может назначать заявку для сектора
        'requests_set_null_status', # Может устанавливать заявке статус "Не обработано"
        'requests_show_resource', # Может просматривать статистику обращений
        'second_calls_access', # Доступ ко вторичным звонкам
        'sms_access', # Доступ к смс сообщениям
        'sms_access_system', # Доступ к сообщениям без заявки
        'user_fines_access', # Доступ к разделу штрафов
        'user_fines_create', # Доступ к разделу штрафов
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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function start(Request $request)
    {
        // Формирование кнопок в верхушке сайта
        $menu = [];

        // Проверка прав пользователя
        $permits = $request->user()->getListPermits(self::$permitsList);

        // Список всех вкладок, доступных пользователю
        $request->tabs = $request->user()->getAllTabs();
        $tabs = $request->tabs->map(function ($tab) {
                return [
                    'id' => $tab->id,
                    'name' => $tab->name,
                    'name_title' => $tab->name_title,
                ];
            });

        $statuses = $request->user()->getStatusesList()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'theme' => $row->theme,
                ];
            });

        return response()->json([
            'tabs' => $tabs,
            'permits' => $permits,
            'topMenu' => $menu,
            'intervalCounter' => self::getCounterUpdateInterval(),
            // 'counter' => Counters::getCounterData($request),
            'counter' => [],
            'themes' => Themes::$data,
            'statuses' => $statuses,
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
