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
    ];

    /**
     * Проверенный список разрешений
     * 
     * @var array
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
