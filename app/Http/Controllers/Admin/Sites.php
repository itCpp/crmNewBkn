<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ExceptionsJsonResponse;
use App\Http\Controllers\Admin\Blocks\Statistics;
use App\Http\Controllers\Controller;
use App\Models\Company\StatVisitSite;
use Illuminate\Http\Request;
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
}
