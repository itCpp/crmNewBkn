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

        return response()->json([
            'tabs' => $request->__user->getAllTabs(),
            'permits' => $request->__user->getListPermits(RequestStart::$permitsList),
            'topMenu' => $menu,
        ]);

    }

}
