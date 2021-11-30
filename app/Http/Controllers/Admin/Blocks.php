<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Blocks\Statistics;
use Illuminate\Http\Request;

class Blocks extends Controller
{
    /**
     * Вывод статистики посещений
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function statistic(Request $request)
    {
        return response()->json(
            (new Statistics($request))->getStatistic()
        );
    }
}
