<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ExceptionsJsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Company\AllVisit;
use App\Models\Company\AutoBlockHost;
use App\Models\Company\BlockHost;
use App\Models\Company\StatVisit;
use App\Models\Company\StatVisitSite;
use App\Models\CrmMka\CrmRequestsQueue;
use App\Models\RequestsQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Sites extends Controller
{
    /**
     * IP адреса в статистике
     * 
     * @var array
     */
    protected $ips = [];

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
        $this->data = [];
        $this->sites = [
            $request->site,
            "www." . $request->site
        ];

        // Посещения за текущий день
        AllVisit::selectRaw('count(*) as count, ip')
            ->whereIn('site', $this->sites)
            ->whereDate('created_at', now())
            ->groupBy('ip')
            ->get()
            ->each(function ($row) {
                $this->ips[] = $row->ip;

                $this->data[md5($row->ip)] = [
                    'ip' => $row->ip,
                    'visits' => $row->count,
                ];
            });

        // Общее количество посещений
        AllVisit::selectRaw('count(*) as count, ip')
            ->whereIn('ip', $this->ips)
            ->whereIn('site', $this->sites)
            ->groupBy('ip')
            ->get()
            ->each(function ($row) {
                $this->data[md5($row->ip)]['visitsAll'] = $row->count;
            });

        // Количество оставленных заявок
        StatVisitSite::whereIn('ip', $this->ips)
            ->whereDate('date', now())
            ->whereIn('site', $this->sites)
            ->get()
            ->each(function ($row) {
                $this->data[md5($row->ip)]['requests'] = $row->requests;
            });

        // Количество оставленных заявок за все время
        StatVisitSite::selectRaw('sum(requests) as count, ip')
            ->whereIn('ip', $this->ips)
            ->whereIn('site', $this->sites)
            ->groupBy('ip')
            ->get()
            ->each(function ($row) {
                $this->data[md5($row->ip)]['requestsAll'] = (int) $row->count;
            });

        // Статистика по очередям
        $this->getQueuesData();

        // Поиск имени хоста
        StatVisit::whereIn('ip', $this->ips)
            ->whereDate('date', now())
            ->get()
            ->each(function ($row) {
                $this->data[md5($row->ip)]['host'] = $row->host;
            });

        // Информация о блокировках
        BlockHost::whereIn('host', $this->ips)
            ->whereIsHostname(0)
            ->whereBlock(1)
            ->get()
            ->each(function ($row) {
                $this->data[md5($row->host)]['blocked_on'] = true;
            });

        // Автоматические блокировки
        AutoBlockHost::whereIn('ip', $this->ips)
            ->whereDate('date', now())
            ->distinct()
            ->get()
            ->each(function ($row) {
                $this->data[md5($row->ip)]['autoblock'] = true;
            });

        $this->data = collect($this->data)
            ->map(function ($row) {
                return array_merge($row, [
                    'autoblock' => $row['autoblock'] ?? false,
                    'blocked_on' => $row['blocked_on'] ?? false,
                    'requests' => $row['requests'] ?? 0,
                    'requestsAll' => $row['requestsAll'] ?? 0,
                    'visits' => $row['visits'] ?? 0,
                    'visitsAll' => $row['visitsAll'] ?? 0,
                    'queues' => $row['queues'] ?? 0,
                    'queuesAll' => $row['queuesAll'] ?? 0,
                ]);
            })
            ->values()
            ->all();

        usort($this->data, function ($a, $b) {
            $c = (int) $b['blocked_on'] - (int) $a['blocked_on'];
            $c .= (int) $b['autoblock'] - (int) $a['autoblock'];
            $c .= $b['requests'] - $a['requests'];
            $c .= $b['queues'] - $a['queues'];
            $c .= $b['visits'] - $a['visits'];
            return $c;
        });

        return $this->data;
    }

    /**
     * Статистические данные по очередям
     * 
     * @return $this
     * 
     * @todo Добавить миграцию на создание индексов в таблице очередей
     */
    public function getQueuesData()
    {
        if (env("NEW_CRM_OFF", true))
            return $this->getQueuesDataFromOldCrm();

        RequestsQueue::selectRaw('count(*) as count, ip')
            ->whereIn('ip', $this->ips)
            ->whereIn('site', $this->sites)
            ->whereDate('created_at', now())
            ->groupBy('ip')
            ->get()
            ->each(function ($row) {
                $this->data[md5($row->ip)]['queues'] = (int) $row->count;
            });

        RequestsQueue::selectRaw('count(*) as count, ip')
            ->whereIn('ip', $this->ips)
            ->whereIn('site', $this->sites)
            ->groupBy('ip')
            ->get()
            ->each(function ($row) {
                $this->data[md5($row->ip)]['queuesAll'] = (int) $row->count;
            });

        return $this;
    }

    /**
     * Статистические данные по очередям из старых таблиц
     * 
     * @return $this
     */
    public function getQueuesDataFromOldCrm()
    {
        CrmRequestsQueue::selectRaw('count(*) as count, ip')
            ->whereIn('ip', $this->ips)
            ->whereIn('site', $this->sites)
            ->whereDate('created_at', now())
            ->groupBy('ip')
            ->get()
            ->each(function ($row) {
                $this->data[md5($row->ip)]['queues'] = (int) $row->count;
            });

        CrmRequestsQueue::selectRaw('count(*) as count, ip')
            ->whereIn('ip', $this->ips)
            ->whereIn('site', $this->sites)
            ->groupBy('ip')
            ->get()
            ->each(function ($row) {
                $this->data[md5($row->ip)]['queuesAll'] = (int) $row->count;
            });

        return $this;
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
