<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RequestStart extends Controller
{
    
    /**
     * Подготовка данных для вывода страницы заявок
     * 
     * @param \Iluminate\Http\Request $request
     * @return response
     */
    public static function start(Request $request)
    {

        return response()->json([
            'tabs' => $request->__user->getModel()->tabs,
        ]);

    }

}
