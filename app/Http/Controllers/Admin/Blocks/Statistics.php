<?php

namespace App\Http\Controllers\Admin\Blocks;

use App\Http\Controllers\Controller;
use App\Models\Company\AllVisit;
use App\Models\RequestsQueue;
use App\Models\Company\BlockHost;
use App\Models\Company\DropCount;
use App\Models\Company\StatVisit;
use App\Models\Company\StatVisitSite;
use App\Models\Company\StatRequest;
use App\Models\Company\AutoBlockHost;
use App\Models\Company\BlockIdSite;
use App\Models\CrmMka\CrmRequestsQueue;
use App\Models\IpInfo;
use Illuminate\Http\Request;

class Statistics extends Controller
{
    /**
     * Колчество дней для вывода статистики по IP
     * 
     * @var int
     */
    const DAYS = 31;

    /**
     * Создание экземпляра объекта
     * 
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(
        protected Request $request,
        public array $data = [],
        public array $sites = []
    ) {
        $this->date = $request->date ?: date("Y-m-d");
        $this->ips = [];

        $this->our_ips = explode(",", env("OUR_IP_ADDRESSES_LIST", ""));
        $this->not_in_sites = explode(",", env("SITES_NOT_IN_BLOCK_STATS", ""));
    }

    /**
     * Подсчет статистики
     * 
     * @param null|string $ip
     * @return array
     */
    public function getStatistic($ip = null)
    {
        /** Подсчет данных за текущий день */
        StatVisitSite::selectRaw('sum(count) as count, sum(count_block) as count_block, sum(requests) as requests, ip')
            ->whereDate('date', $this->date)
            ->when(is_string($ip), function ($query) use ($ip) {
                $query->where('ip', $ip);
            })
            ->when(count($this->sites) > 0, function ($query) {
                $query->whereIn('site', $this->sites);
            })
            ->when((count($this->sites) === 0 and count($this->not_in_sites) > 0), function ($query) {
                $query->whereNotIn('site', $this->not_in_sites);
            })
            ->groupBy('ip')
            ->get()
            ->each(function ($row) {
                $this->ips[] = $row->ip;
                $this->data[$row->ip] = [
                    'ip' => $row->ip,
                    'visits' => (int) $row->count,
                    'requests' => (int) $row->requests,
                    'visitsBlock' => (int) $row->count_block,
                ];
            });

        $this->ips = array_unique($this->ips);

        /** Общие цифры */
        StatVisitSite::selectRaw('sum(count) as count, sum(count_block) as count_block, sum(requests) as requests, ip')
            ->whereIn('ip', $this->ips)
            ->when(count($this->sites) > 0, function ($query) {
                $query->whereIn('site', $this->sites);
            })
            ->when((count($this->sites) === 0 and count($this->not_in_sites) > 0), function ($query) {
                $query->whereNotIn('site', $this->not_in_sites);
            })
            ->groupBy('ip')
            ->get()
            ->each(function ($row) {
                $this->data[$row->ip]['all'] = (int) $row->count;
                $this->data[$row->ip]['requestsAll'] = (int) $row->requests;
                $this->data[$row->ip]['drops'] = (int) $row->count_block;
            });

        $this->getQueuesData();

        /** Информация о блокировках */
        BlockHost::whereIn('host', $this->ips)
            ->whereIsHostname(0)
            ->whereBlock(1)
            ->get()
            ->each(function ($row) {
                $this->data[$row->host]['blocked'] = true;
                $this->data[$row->host]['blocked_on'] = $row->block === 1;
            });

        /** Информация о блокировках по ID */
        BlockIdSite::whereIn('ip', $this->ips)
            ->where('deleted_at', null)
            ->get()
            ->each(function ($row) {
                $this->data[$row->ip]['blocked_client_id'] = $row->deleted_at === null;
            });

        /** Автоматические блокировки */
        AutoBlockHost::whereIn('ip', $this->ips)
            ->whereDate('date', now())
            ->distinct()
            ->get()
            ->each(function ($row) {
                $this->data[$row->ip]['autoblock'] = true;
            });

        /** Поиск имени хоста */
        StatVisit::whereIn('ip', $this->ips)
            ->whereDate('date', $this->date)
            ->get()
            ->each(function ($row) {
                $this->data[$row->ip]['host'] = $row->host;
            });

        /** Информация об IP */
        IpInfo::select('ip', 'country_code', 'region_name', 'city')
            ->whereIn('ip', $this->ips)
            ->get()
            ->each(function ($row) {
                $this->data[$row->ip]['city'] = $row->city;
                $this->data[$row->ip]['country_code'] = $row->country_code;
                $this->data[$row->ip]['region_name'] = $row->region_name;
                $this->data[$row->ip]['info'] = $row->toArray();
            });

        return collect($this->data)->map(function ($row) {
            return array_merge([
                'all' => $row['all'] ?? 0,
                'autoblock' => $row['autoblock'] ?? false,
                'blocked' => $row['blocked'] ?? false,
                'blocked_on' => $row['blocked_on'] ?? false,
                'blocked_sort' => (int) (($row['blocked'] ?? false) || ($row['autoblock'] ?? false)),
                'city' => $row['city'] ?? null,
                'country_code' => $row['country_code'] ?? null,
                'drops' => $row['drops'] ?? 0,
                'host' => $row['host'] ?? null,
                'info' => $row['info'] ?? null,
                'queues' => $row['queues'] ?? 0,
                'queuesAll' => $row['queuesAll'] ?? 0,
                'region_name' => $row['region_name'] ?? null,
                'requests' => $row['requests'] ?? 0,
                'requestsAll' => $row['requestsAll'] ?? 0,
                'visits' => $row['visits'] ?? 0,
                'visitsAll' => $row['all'] ?? 0,
                'visitsBlock' => $row['drops'] ?? 0,
                'our_ip' => in_array($row['ip'] ?? null, $this->our_ips),
            ], $row);
        })
            ->sortBy([
                ['blocked_sort', 'desc'],
                ['visits', 'desc'],
                ['drops', 'desc'],
            ])
            ->values()
            ->all();
    }

    /**
     * Статистика по очередям
     * 
     * @return $this
     * 
     * @todo Добавить миграцию на создание индексов в таблице очередей
     */
    public function getQueuesData()
    {
        if (env("NEW_CRM_OFF", true))
            return $this->getQueuesFromOldCrm();

        RequestsQueue::selectRaw('count(*) as count, ip')
            ->whereIn('ip', $this->ips)
            ->when(count($this->sites) > 0, function ($query) {
                $query->whereIn('site', $this->sites);
            })
            ->whereDate('created_at', $this->date)
            ->groupBy('ip')
            ->get()
            ->each(function ($row) {
                $this->data[md5($row->ip)]['queues'] = (int) $row->count;
            });

        RequestsQueue::selectRaw('count(*) as count, ip')
            ->whereIn('ip', $this->ips)
            ->when(count($this->sites) > 0, function ($query) {
                $query->whereIn('site', $this->sites);
            })
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
    public function getQueuesFromOldCrm()
    {
        CrmRequestsQueue::selectRaw('count(*) as count, ip')
            ->whereIn('ip', $this->ips)
            ->when(count($this->sites) > 0, function ($query) {
                $query->whereIn('site', $this->sites);
            })
            ->whereDate('created_at', $this->date)
            ->groupBy('ip')
            ->get()
            ->each(function ($row) {
                $this->data[$row->ip]['queues'] = (int) $row->count;
            });

        CrmRequestsQueue::selectRaw('count(*) as count, ip')
            ->whereIn('ip', $this->ips)
            ->when(count($this->sites) > 0, function ($query) {
                $query->whereIn('site', $this->sites);
            })
            ->groupBy('ip')
            ->get()
            ->each(function ($row) {
                $this->data[$row->ip]['queuesAll'] = (int) $row->count;
            });

        return $this;
    }

    /**
     * Вывод статистики
     * 
     * @param null|string $ip
     * @return array
     */
    public function getStatisticOld($ip = null)
    {
        $this->getDrops()
            ->getVisits()
            ->getStatRequests()
            ->getQueues()
            ->getStatAllDays()
            ->getBlockData()
            ->getIpInfo();

        $response = [];

        foreach ($this->data as $ip => $row) {

            $host = $row['host'] ?? null;
            $block = false;

            // Проверка включенной блокировки
            $blocked_on = $this->blockedData[$ip] ?? null;

            if ($host) {
                foreach ($this->blocked as $brow) {
                    if (strpos($brow, $host) !== false) {
                        $block = true;
                        $blocked_on = $this->blockedData[$brow] ?? null;
                        break;
                    }
                }
            }

            $blocked_bool = in_array($ip, $this->blocked) || $block;
            $blocked_on = $blocked_on ?? null;
            $blocked_sort = $blocked_bool && $blocked_on;

            $response['rows'][] = [
                'ip' => $ip,
                'host' => $host,
                'visits' => $row['visits'] ?? 0,
                'requests' => $row['requests'] ?? 0,
                'drops' => $row['drops'] ?? 0,
                'queues' => $row['queues'] ?? 0,
                'queuesAll' => $row['queuesAll'] ?? 0,
                'autoblock' => $this->autoBlock[$ip] ?? false,
                'blocked' => $blocked_bool,
                'blocked_on' => $blocked_on,
                'all' => $row['all'] ?? 0,
                'blocked_sort' => $blocked_sort,
                'requestsAll' => $row['requestsAll'] ?? 0,
                'info' => $row['info'] ?? null,
            ];
        }

        usort($response['rows'], function ($a, $b) {
            $c = (int) $b['blocked_sort'] - (int) $a['blocked_sort'];
            $c .= $b['drops'] - $a['drops'];
            $c .= $b['requests'] - $a['requests'];
            $c .= $b['queues'] - $a['queues'];
            $c .= $b['visits'] - $a['visits'];
            return $c;
        });

        $response['date'] = $this->date ?? date("Y-m-d");

        return $response;
    }

    /**
     * Вывод данных по IP
     * 
     * @param null|int $ip
     * @return array
     * 
     * @todo Вернуть расчет статистики по заявкам
     */
    public function getStatisticIp($ip = null)
    {
        $dates = [];
        $time = strtotime($this->date) - (86400 * self::DAYS);

        $names = [
            'count' => "Посещения сайта",
            'block' => "Блокированные посещения",
            'requests' => "Оставлено заявок",
        ];

        for ($i = 1; $i <= self::DAYS; $i++) {
            $time += 86400;
            $date = date("Y-m-d", $time);
            $dates[] = $date;
            $this->data['dates'][$date] = [];
        }

        StatVisitSite::whereIp($ip)
            ->whereIn('date', $dates)
            ->orderBy('date')
            ->get()
            ->map(function ($row) {
                $this->data['dates'][$row->date][] = $row;
            });

        $chart = [];

        foreach ($this->data['dates'] as $date => $rows) {

            $day = date("d.m.Y", strtotime($date));

            foreach ($rows as $row) {

                $site = idn_to_utf8($row->site);

                if ($row->count > 0) {
                    $chart['count'][] = [
                        'name' => $site,
                        'date' => $date,
                        'day' => $day,
                        'value' => $row->count,
                    ];
                }

                if ($row->count_block > 0) {
                    $chart['block'][] = [
                        'name' => $site,
                        'date' => $date,
                        'day' => $day,
                        'value' => $row->count_block,
                    ];
                }

                // if ($row->requests > 0) {
                //     $chart['requests'][] = [
                //         'name' => $site,
                //         'date' => $date,
                //         'day' => $day,
                //         'value' => $row->requests,
                //     ];
                // }
            }
        }

        foreach ($chart as $type => $data) {
            $this->data['chart'][] = [
                'name' => $names[$type] ?? $type,
                'data' => $data,
            ];
        }

        return $this->data;
    }

    /**
     * Получение информации о блокированных посещениях
     * 
     * @return $this
     */
    public function getDrops()
    {
        DropCount::selectRaw('SUM(count) as count, ip')
            ->where('date', $this->date)
            ->groupBy('ip')
            ->get()
            ->map(function ($row) {

                if (!in_array($row->ip, $this->ips))
                    $this->ips[] = $row->ip;

                if (!isset($this->data[$row->ip]['drops']))
                    $this->data[$row->ip]['drops'] = 0;

                $this->data[$row->ip]['drops'] += $row->count;
            });

        return $this;
    }

    /**
     * Информация о блокированных посещениях за все время
     * 
     * @return $this
     */
    public function getDropsAllDays()
    {
        DropCount::selectRaw('SUM(count) as count, ip')
            ->whereIn('ip', $this->ips)
            ->groupBy('ip')
            ->get()
            ->map(function ($row) {

                if (!isset($this->data[$row->ip]['all']))
                    $this->data[$row->ip]['all'] = 0;

                $this->data[$row->ip]['all'] += $row->count;
            });

        return $this;
    }

    /**
     * Информация о посещениях
     * 
     * @return $this
     */
    public function getVisits()
    {
        // AllVisit::selectRaw('count(*) as count, ip')
        //     ->whereDate('created_at', $this->date)
        //     ->groupBy('ip')
        //     ->get()
        //     ->each(function ($row) {

        //         if (!in_array($row->ip, $this->ips))
        //             $this->ips[] = $row->ip;

        //         if (!isset($this->data[$row->ip]['visits']))
        //             $this->data[$row->ip]['visits'] = 0;

        //         $this->data[$row->ip]['visits'] += $row->count;
        //     });

        StatVisit::selectRaw('SUM(count) as count, ip, host')
            ->where('date', $this->date)
            ->groupBy(['ip', 'host'])
            // ->having('count', '>', 3)
            ->get()
            ->map(function ($row) {

                if (!in_array($row->ip, $this->ips))
                    $this->ips[] = $row->ip;

                if (!isset($this->data[$row->ip]['visits']))
                    $this->data[$row->ip]['visits'] = 0;

                $this->data[$row->ip]['visits'] += $row->count;
                $this->data[$row->ip]['host'] = $row->host;
            });

        return $this;
    }

    /**
     * Информация о посещениях за все время
     * 
     * @return $this
     */
    public function getVisitsAllDays()
    {
        // AllVisit::selectRaw('count(*) as count, ip')
        //     ->whereIn('ip', $this->ips)
        //     ->groupBy('ip')
        //     ->get()
        //     ->each(function ($row) {

        //         if (!isset($this->data[$row->ip]['all']))
        //             $this->data[$row->ip]['all'] = 0;

        //         $this->data[$row->ip]['all'] += $row->count;
        //     });

        StatVisit::selectRaw('SUM(count) as count, ip')
            ->whereIn('ip', $this->ips)
            ->groupBy('ip')
            ->get()
            ->map(function ($row) {

                if (!isset($this->data[$row->ip]['all']))
                    $this->data[$row->ip]['all'] = 0;

                $this->data[$row->ip]['all'] += $row->count;
            });

        return $this;
    }

    /**
     * Информация об оставленных запросах на заявку
     * 
     * @return $this
     */
    public function getStatRequests()
    {
        StatRequest::selectRaw('SUM(count) as count, ip')
            ->where('date', $this->date)
            ->groupBy('ip')
            ->get()
            ->map(function ($row) {

                if (!in_array($row->ip, $this->ips))
                    $this->ips[] = $row->ip;

                if (!isset($this->data[$row->ip]['requests']))
                    $this->data[$row->ip]['requests'] = 0;

                $this->data[$row->ip]['requests'] += $row->count;
            });

        return $this;
    }

    /**
     * Информация о блокированных посещениях за все время
     * 
     * @return $this
     */
    public function getStatRequestsAllDays()
    {
        StatRequest::selectRaw('SUM(count) as count, ip')
            ->whereIn('ip', $this->ips)
            ->groupBy('ip')
            ->get()
            ->map(function ($row) {

                if (!isset($this->data[$row->ip]['requestsAll']))
                    $this->data[$row->ip]['requestsAll'] = 0;

                $this->data[$row->ip]['requestsAll'] += $row->count;
            });

        return $this;
    }

    /**
     * Информация об очередях
     * 
     * @return $this
     */
    public function getQueues()
    {
        if (env("NEW_CRM_OFF", true))
            return $this->getQueuesDataFromOldCrm();

        RequestsQueue::selectRaw('COUNT(*) as count, ip')
            ->whereDate('created_at', $this->date)
            ->groupBy('ip')
            ->get()
            ->map(function ($row) {

                if (!isset($this->data[$row->ip]['queues']))
                    $this->data[$row->ip]['queues'] = 0;

                $this->data[$row->ip]['queues'] += $row->count;
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
            ->whereDate('created_at', $this->date)
            ->groupBy('ip')
            ->get()
            ->each(function ($row) {

                if (!isset($this->data[$row->ip]['queues']))
                    $this->data[$row->ip]['queues'] = 0;

                $this->data[$row->ip]['queues'] += $row->count;
            });

        return $this;
    }

    /**
     * Информация об очередях
     * 
     * @return $this
     */
    public function getQueuesAllDays()
    {
        if (env("NEW_CRM_OFF", true))
            return $this->getQueuesAllDaysDataFromOldCrm();

        RequestsQueue::selectRaw('COUNT(*) as count, ip')
            ->whereIn('ip', $this->ips)
            ->groupBy('ip')
            ->get()
            ->map(function ($row) {

                if (!isset($this->data[$row->ip]['queuesAll']))
                    $this->data[$row->ip]['queuesAll'] = 0;

                $this->data[$row->ip]['queuesAll'] += $row->count;
            });

        return $this;
    }

    /**
     * Статистические данные по очередям из старых таблиц
     * 
     * @return $this
     */
    public function getQueuesAllDaysDataFromOldCrm()
    {
        CrmRequestsQueue::selectRaw('count(*) as count, ip')
            ->whereIn('ip', $this->ips)
            ->groupBy('ip')
            ->get()
            ->each(function ($row) {

                if (!isset($this->data[$row->ip]['queuesAll']))
                    $this->data[$row->ip]['queuesAll'] = 0;

                $this->data[$row->ip]['queuesAll'] += $row->count;
            });

        return $this;
    }

    /**
     * Подсчет статистики за все время
     * 
     * @return $this
     */
    public function getStatAllDays()
    {
        $this->getDropsAllDays()
            ->getVisitsAllDays()
            ->getStatRequestsAllDays()
            ->getQueuesAllDays();

        return $this;
    }

    /**
     * Информация о блокировке
     * 
     * @return $this
     */
    public function getBlockData()
    {
        $this->autoBlock = [];
        $this->blocked = [];
        $this->blockedData = [];

        AutoBlockHost::whereIn('ip', $this->ips)
            ->where('date', $this->date)
            ->get()
            ->map(function ($row) {
                $this->autoBlock[$row->ip] = true;
            });

        BlockHost::whereIn('host', $this->ips)
            ->get()
            ->map(function ($row) {
                $this->blocked[] = $row->host;
                $this->blockedData[$row->host] = $row->block == 1 ? true : false;
            });

        return $this;
    }

    /**
     * Информация об ip адресах
     * 
     * @return $this
     */
    public function getIpInfo()
    {
        IpInfo::select('ip', 'country_code', 'region_name', 'city')
            ->whereIn('ip', $this->ips)
            ->get()
            ->each(function ($row) {
                $this->data[$row->ip]['info'] = $row->toArray();
            });

        return $this;
    }
}
