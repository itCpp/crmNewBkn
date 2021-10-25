<?php

namespace App\Http\Controllers\Users;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Формирование и работа страницы с личными данными сотрудника
 */
class UserMainData
{
    /**
     * Метод сбора данных для страницы
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public static function getUserMainData(Request $request)
    {
        return Response::json();
    }
}