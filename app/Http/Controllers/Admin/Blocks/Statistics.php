<?php

namespace App\Http\Controllers\Admin\Blocks;

use App\Http\Controllers\Controller;
use App\Models\RequestsQueue;
use App\Models\Company\BlockHost;
use App\Models\Company\DropCount;
use App\Models\Company\StatVisit;
use App\Models\Company\StatVisitSite;
use App\Models\Company\StatRequest;
use App\Models\Company\AutoBlockHost;
use Illuminate\Http\Request;

class Statistics extends Controller
{
    /**
     * Создание экземпляра объекта
     * 
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(
        protected Request $request,
        protected array $data = []
    ) {
        $this->date = $request->date ?? date("Y-m-d");
        $this->ips = [];
    }

    /**
     * Вывод статистики
     * 
     * @param null|string $ip
     * @return array
     */
    public function getStatistic($ip = null)
    {
        $this->getDrops()
            ->getVisits()
            ->getStatRequests()
            ->getQueues()
            ->getStatAllDays()
            ->getBlockData();

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
     */
    public function getStatisticIp($ip = null)
    {
        $dates[] = $this->date;
        $time = strtotime($this->date);

        $names = [
            'count' => "Посещения сайта",
            'block' => "Блокированные посещения",
            'requests' => "Оставлено заявок",
        ];

        for ($i = 1; $i < 14; $i++) {
            $time -= 86400;
            $date = date("Y-m-d", $time);
            $dates[] = $date;
            $this->data['dates'][$date] = [];
        }

        StatVisitSite::whereIp($ip)
            ->whereIn('date', $dates)
            ->orderBy('date', "DESC")
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

                if ($row->requests > 0) {
                    $chart['requests'][] = [
                        'name' => $site,
                        'date' => $date,
                        'day' => $day,
                        'value' => $row->requests,
                    ];
                }
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
     * Информация о блокированных посещениях за все время
     * 
     * @return $this
     */
    public function getVisitsAllDays()
    {
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
     * Информация об очередях
     * 
     * @return $this
     */
    public function getQueuesAllDays()
    {
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
}
