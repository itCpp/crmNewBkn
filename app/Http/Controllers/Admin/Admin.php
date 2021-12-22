<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Users\Users;
use App\Models\Company\AllVisit;
use App\Models\Company\StatVisit;
use Illuminate\Http\Request;

class Admin extends Controller
{
    /**
     * Колчеиство месяцев для вывода общей статистики
     * 
     * @var int
     */
    const MONTHS = 5;

    /**
     * Данные для главной страницы админки
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function start(Request $request)
    {
        $response = Users::adminCheck($request, true);

        $response['views'] = self::getViews($request);
        $response['hosts'] = self::getHosts($request);

        return response()->json($response);
    }

    /**
     * Статистика посещений за последние 7 дней
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public static function getViews(Request $request)
    {
        return AllVisit::selectRaw('COUNT(*) as count, DATE(created_at) as created_date')
            ->whereDate('created_at', '>=', now()->addMonth(-(self::MONTHS)))
            ->groupBy('created_date')
            ->get()
            ->toArray();
    }

    /**
     * Уникальные посетители на сайтах
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public static function getHosts(Request $request)
    {
        return StatVisit::selectRaw('COUNT(*) as count, date as created_date')
            ->where('date', '>=', now()->addMonth(-(self::MONTHS)))
            ->groupBy('created_date')
            ->get()
            ->toArray();
    }
}
