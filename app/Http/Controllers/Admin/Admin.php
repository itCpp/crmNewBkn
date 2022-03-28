<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Users\Users;
use App\Models\Company\AllVisit;
use App\Models\Company\StatVisit;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Admin extends Controller
{
    /**
     * Колчеиство месяцев для вывода общей статистики
     * 
     * @var int
     */
    const MONTHS = 3;

    /**
     * Данные для главной страницы админки
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function start(Request $request)
    {
        $response = Users::adminCheck($request, true);

        $this->databases = Databases::setConfigs();

        $response['views'] = $this->getViews($request);
        $response['hosts'] = $this->getHosts($request);

        return response()->json($response);
    }

    /**
     * Статистика посещений за последние 7 дней
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function getViews(Request $request)
    {
        $data = [];

        foreach ($this->databases as $connection) {

            try {
                DB::connection($connection)
                    ->table('statistics')
                    ->selectRaw('SUM(visits + visits_drops) as count, date')
                    ->where('date', '>=', now()->subMonths(self::MONTHS))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->each(function ($row) use (&$data) {
                        if (!isset($data[$row->date]))
                            $data[$row->date] = 0;

                        $data[$row->date] += $row->count;
                    });
            } catch (Exception) {
            }
        }

        return collect($data)
            ->map(function ($row, $key) {
                return [
                    'count' => $row,
                    'created_date' => $key,
                ];
            })
            ->values()
            ->all();

        // return AllVisit::selectRaw('COUNT(*) as count, DATE(created_at) as created_date')
        //     ->where('created_at', '>=', now()->addMonth(- (self::MONTHS)))
        //     ->groupBy('created_date')
        //     ->get()
        //     ->toArray();
    }

    /**
     * Уникальные посетители на сайтах
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function getHosts(Request $request)
    {
        return StatVisit::selectRaw('COUNT(*) as count, date as created_date')
            ->where('date', '>=', now()->addMonth(- (self::MONTHS)))
            ->groupBy('created_date')
            ->get()
            ->toArray();
    }
}
