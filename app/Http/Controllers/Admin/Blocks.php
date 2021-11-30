<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Blocks\Statistics;
use App\Models\Company\BlockHost;
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

    /**
     * Блокировка ip адреса
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function setBlockIp(Request $request)
    {
        if (!$row = BlockHost::where('host', $request->ip)->first())
            $row = BlockHost::create(['host' => $request->ip]);

        $block = (bool) $row->block;
        $row->block = (int) !$block;

        $row->save();

        // Логировние
        parent::logData($request, $row);

        return response()->json([
            'blocked' => true, // Наличие в черном списке
            'blocked_on' => $row->block == 1 ? true : false, // Вкл/Выкл блокировка
            'row' => $row,
        ]);
    }
}
