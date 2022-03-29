<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Ratings\Ratings;
use App\Http\Controllers\Users\Users;
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
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function start(Request $request)
    {
        $response = Users::adminCheck($request, true);

        $response['rating_chart'] = Ratings::getChartData($request);

        return response()->json(array_merge(
            $response,
            $this->getStats($request)
        ));
    }

    /**
     * Статистика посещений
     * 
     * @param \Illuminate\Http\Request
     * @return array
     */
    public function getStats(Request $request)
    {
        if (empty($this->databases))
            $this->databases = Databases::setConfigs();

        return [
            'views' => $this->getViews($request),
            'hosts' => $this->getHosts($request),
        ];
    }

    /**
     * Статистика посещений
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
    }

    /**
     * Уникальные посетители на сайтах
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function getHosts(Request $request)
    {
        $data = [];

        foreach ($this->databases as $connection) {

            try {
                DB::connection($connection)
                    ->table('statistics')
                    ->selectRaw('COUNT(*) as count, date')
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
    }
}
