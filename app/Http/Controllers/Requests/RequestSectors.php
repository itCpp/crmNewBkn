<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RequestSectors extends Controller
{
    
    /**
     * Вывод списка секторов для выдачи заявки в нужный сектор
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function changeSectorShow(Request $request)
    {

        // Проверка необходимых разрешений
        $permits = $request->__user->getListPermits([
            'requests_sector_set', # Может назначать заявку для сектора
            'requests_sector_change', # Может менять сектор в заявке
        ]);

        return response()->json();

    }

    /**
     * Передача заявки сектору
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function setSector(Request $request)
    {

        return response()->json();

    }

}
