<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ExceptionsJsonResponse;
use App\Http\Controllers\Admin\Blocks\Statistics;
use App\Http\Controllers\Controller;
use App\Models\Company\StatVisitSite;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Sites extends Controller
{
    /**
     * Вывод списка сайтов
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function sites()
    {
        return response()->json($this->getSitesList());
    }

    /**
     * Вывод списка сайтов
     * 
     * @return array
     */
    public static function getSitesList()
    {
        return Str::of(env("SITES_FOR_STATS", ""))
            ->explode(",")
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Вывод статистики по сайтам
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sitesStats(Request $request)
    {
        $this->checkDomain($request->site);

        return response()->json([
            'rows' => $this->getSiteStatistic($request),
        ]);
    }

    /**
     * Вывод данных графика
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChartSite(Request $request)
    {
        $this->checkDomain($request->site);

        return response()->json([
            'chart' => $this->getChartDataSiteVisits($request->site),
        ]);
    }

    /**
     * Проверка домена
     * 
     * @param string
     * @return null
     * 
     * @throws \App\Exceptions\ExceptionsJsonResponse
     */
    public function checkDomain($site)
    {
        if (!$site)
            throw new ExceptionsJsonResponse("Адрес сайта не выбран");

        if (!in_array($site, $this->getSitesList()))
            throw new ExceptionsJsonResponse("Данный сайт не найден среди разрешенного списка");

        return null;
    }

    /**
     * Статистика посещений по сайту
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function getSiteStatistic(Request $request)
    {
        $sites = [$request->site];

        if (!Str::substrCount($request->site, 'www.'))
            $sites[] = "www." . $request->site;

        return (new Statistics($request, sites: $sites))->getStatistic();
    }

    /**
     * Данные для графика посещений сайта
     * 
     * @param string $site
     * @param null|string $date
     * @return array
     */
    public static function getChartDataSiteVisits($site, $date = null)
    {
        $sites = [$site, "www." . $site];
        $date = $date ?: now();

        $data = [];

        StatVisitSite::selectRaw('sum(count + count_block) as count, date')
            ->whereIn('site', $sites)
            ->where(function ($query) use ($date) {
                $query->whereDate('date', '>=', date("Y-m-d", strtotime($date) - 90 * 24 * 60 * 60))
                    ->whereDate('date', '<=', $date);
            })
            ->groupBy('date')
            ->get()
            ->each(function ($row) use (&$data) {
                $key = strtotime($row->date) . "views";

                if (empty($data[$key])) {
                    $data[$key] = [
                        'date' => $row->date,
                        'name' => "views",
                        'value' => 0,
                    ];
                }

                $data[$key]['value'] += $row->count;
            });

        StatVisitSite::selectRaw('count(*) as count, ip, date')
            ->whereIn('site', $sites)
            ->where(function ($query) use ($date) {
                $query->whereDate('date', '>=', date("Y-m-d", strtotime($date) - 90 * 24 * 60 * 60))
                    ->whereDate('date', '<=', $date);
            })
            ->groupBy(['ip', 'date'])
            ->get()
            ->each(function ($row) use (&$data) {
                $key = strtotime($row->date) . "hosts";

                if (empty($data[$key])) {
                    $data[$key] = [
                        'date' => $row->date,
                        'name' => "hosts",
                        'value' => 0,
                    ];
                }

                $data[$key]['value'] += $row->count;
            });

        return collect($data)->sortBy('date')->values()->all();
    }

    /**
     * Вывод данных графика
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @throws \App\Exceptions\ExceptionsJsonResponse
     */
    public function getChartSiteOwnStat(Request $request)
    {
        if (!$request->site)
            throw new ExceptionsJsonResponse("Адрес сайта не выбран");

        Databases::setConfigs($request->site);

        $date = $request->date ?: date("Y-m-d");
        $connection = Databases::getConnectionName($request->site);
        $database = DB::connection($connection);

        $data = [];

        $this->own_ips = $this->envExplode('OUR_IP_ADDRESSES_LIST');

        try {
            $database->table('statistics')
                ->selectRaw('sum(visits + visits_drops) as count, date')
                ->whereBetween('date', [
                    date("Y-m-d", time() - 90 * 24 * 60 * 60),
                    $date,
                ])
                ->when(count($this->own_ips ?? []), function ($query) {
                    $query->whereNotIn('ip', $this->own_ips ?? []);
                })
                ->groupBy('date')
                ->get()
                ->each(function ($row) use (&$data) {

                    $key = $row->date . "-views";

                    if (empty($data[$key])) {
                        $data[$key] = [
                            'date' => $row->date,
                            'name' => "views",
                            'value' => 0,
                        ];
                    }

                    $data[$key]['value'] += $row->count;
                });

            $database->table('statistics')
                ->selectRaw('count(*) as count, ip, date')
                ->whereBetween('date', [
                    date("Y-m-d", time() - 90 * 24 * 60 * 60),
                    $date,
                ])
                ->when(count($this->own_ips ?? []), function ($query) {
                    $query->whereNotIn('ip', $this->own_ips ?? []);
                })
                ->groupBy(['ip', 'date'])
                ->get()
                ->each(function ($row) use (&$data) {

                    $key = $row->date . "-hosts";

                    if (empty($data[$key])) {
                        $data[$key] = [
                            'date' => $row->date,
                            'name' => "hosts",
                            'value' => 0,
                        ];
                    }

                    $data[$key]['value'] += $row->count;
                });

            return response()->json([
                'chart' => collect($data)->sortBy('date')->values()->all()
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
