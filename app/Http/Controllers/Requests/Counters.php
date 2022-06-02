<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Offices\OfficesTrait;
use App\Http\Controllers\SecondCalls\SecondCalls;
use App\Http\Controllers\Sms\Sms;
use App\Http\Controllers\Testing\MyTests;
use App\Models\RequestsClientsQuery;
use App\Models\RequestsCounterStory;
use App\Models\RequestsQueue;
use App\Models\RequestsSource;
use Exception;
use Illuminate\Http\Request;

class Counters extends Controller
{
    use OfficesTrait;

    /**
     * Вывод счетчика заявок
     * 
     * @param \Illuminate\Http\Request
     * @return response
     */
    public static function getCounter(Request $request)
    {
        $request->tabs = $request->user()->getAllTabs();

        return response()->json([
            'counter' => self::getCounterData($request),
        ]);
    }

    /**
     * Подсчет счетчика заявок
     * 
     * @param \Illuminate\Http\Request
     * @return array
     */
    public static function getCounterData(Request $request)
    {
        $start = microtime(true);

        $counter = [
            'timecode' => [],
            'flash_null' => 0,
        ];

        $flash_null = optional($request->user())->can('requests_flash_null_status');
        $flash_records = optional($request->user())->can('requests_flash_records_status');

        foreach ($request->tabs as $tab) {

            $key = "tab{$tab->id}";

            $step = microtime(true);
            $request->tab = $tab;

            $query = new RequestsQuery($request);

            $counter[$key] = [
                'id' => $tab->id,
                'name' => $tab->name,
                'count' => $query->count(),
            ];

            $counter['timecode'][$key] = microtime(true) - $step;

            if ($flash_null and $tab->flash_null) {

                if (!isset($counter['btnFlashNull'])) {
                    $counter['btnFlashNull'] = [
                        'count' => 0,
                        'tabs' => [],
                    ];
                }

                $count = $query->model()
                    ->where('status_id', null)
                    ->where('pin', null)
                    ->count();

                $counter['btnFlashNull']['count'] += $count;
                $counter['btnFlashNull']['tabs'][] = $tab->id;
                $counter['timecode']["tab{$tab->id}_btnFlashNull"] = microtime(true) - $step;
            }

            if ($tab->flash_records_confirm and $flash_records) {

                if (!isset($counter['btnRecords'])) {
                    $counter['btnRecords'] = [
                        'count' => 0,
                        'tabs' => [],
                    ];
                }

                $records = parent::envExplode('STATISTICS_OPERATORS_STATUS_RECORD_ID');
                $confirmed = parent::envExplode('STATISTICS_OPERATORS_STATUS_RECORD_CHECK_ID');

                $counter['btnRecords']['count'] += $query->model()
                    ->whereIn('status_id', $records)
                    ->whereNotIn('status_id', $confirmed)
                    ->whereBetween('event_at', [
                        now()->startOfDay()->format("Y-m-d H:i:s"),
                        now()->addHours(2)->format("Y-m-d H:i:s"),
                    ])
                    ->count();

                $counter['btnRecords']['tabs'][] = $tab->id;
                $counter['timecode']["tab{$tab->id}_btnRecords"] = microtime(true) - $step;
            }
        }

        $permits = $request->user()->getListPermits([
            'queues_access',
            'sms_access',
            'second_calls_access'
        ]);

        // Счетчик очереди
        if ($permits->queues_access) {
            $step = microtime(true);
            $counter['queue'] = self::getQueueCounter($request);
            $counter['timecode']['queue'] = microtime(true) - $step;
        }

        if ($permits->sms_access) {
            $step = microtime(true);
            $counter['sms'] = Sms::getCounterNewSms($request);
            $counter['timecode']['sms'] = microtime(true) - $step;
        }

        if ($permits->second_calls_access) {
            $step = microtime(true);
            $counter['secondcalls'] = SecondCalls::getCounterNewSecondCalls($request);
            $counter['timecode']['secondcalls'] = microtime(true) - $step;
        }

        /** Счетчик незавершенных тестирований сотрудника */
        $step = microtime(true);
        $counter['tests'] = MyTests::countTestings(
            $request->user()->pin,
            $request->user()->old_pin
        );
        $counter['timecode']['tests'] = microtime(true) - $step;

        $counter['timecode']['_stop'] = microtime(true) - $start;

        foreach ($counter['timecode'] as &$time) {
            $time = round($time, 3);
        }

        return $counter;
    }

    /**
     * Счетчик необработанной очереди
     * 
     * @param \Illuminate\Http\Request
     * @return array
     */
    public static function getQueueCounter(Request $request)
    {
        return [
            'count' => RequestsQueue::where('done_type', null)->count()
        ];
    }

    /**
     * Формирует данные для страницы счетчика
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @todo По необходимости добавить право `counter_all_data` в общую таблицу
     */
    public function getCounterPage(Request $request)
    {
        $story = [];

        $pin = optional($request->user())->pin;
        $can = (bool) optional($request->user())->can('counter_all_data');

        $show = ((bool) $pin and $can);

        RequestsCounterStory::where('counter_date', '>=', now()->subDays(30)->format("Y-m-d"))
            ->when($show === true, function ($query) {
                $query->where('to_pin', null);
            })
            ->when($show === false, function ($query) use ($request) {
                $query->where('to_pin', optional($request->user())->pin);
            })
            ->get()
            ->each(function ($row) use (&$story) {

                try {
                    $data = decrypt($row->counter_data);
                } catch (Exception) {
                    $data = null;
                }

                if (!is_array($data))
                    return;

                foreach ($data as $key => $counter) {

                    if (!isset($story[$key]))
                        $story[$key] = [];

                    if (!empty($data[$key]))
                        $story[$key][$row->counter_date->format("Y-m-d")] = $data[$key] ?? [];
                }
            });

        $counter = $this->getCounterTabsData($request->user()->getAllTabs(), true);

        return response()->json([
            'counter' => array_values($counter),
            'chart' => $this->chartCounter($counter, $story),
            'date' => now()->format("Y-m-d"),
        ]);
    }

    /**
     * Формирует массив счетчика по вкладкам
     * 
     * @param  array $tabs
     * @param  boolean $hide
     * @return array
     */
    public function getCounterTabsData($tabs = [], $hide = false)
    {
        $offices = $this->getActiveOffices();
        $sources = $this->getActiveSources();

        $request = request();
        $start = $request->start ? now()->create($request->start) : now();

        foreach ($tabs as $tab) {

            if ($hide and $tab->counter_hide_page)
                continue;

            $request->tab = $tab;

            if ($tab->counter_next_day && $request->toNextDay) {
                $merge = [
                    'start' => $start->copy()->addDay()->format("Y-m-d"),
                    'stop' => $start->copy()->addDay()->format("Y-m-d"),
                ];
            } else {
                $merge = [
                    'start' => $start->copy()->format("Y-m-d"),
                    'stop' => $start->copy()->format("Y-m-d"),
                ];
            }

            $request->merge($merge);

            $query = new RequestsQuery($request);

            $data = [
                'id' => $tab->id,
                'name' => $tab->name,
                'count' => $query->count(),
            ];

            if ($tab->counter_offices) {

                foreach ($offices as $office) {
                    $data['offices'][$office['id']] = [
                        'count' => 0,
                        'name' => $office['name'],
                        'id' => $office['id'],
                    ];
                }

                $query->model()
                    ->selectRaw('count(*) as count, address')
                    ->where('address', '!=', null)
                    ->reorder()
                    ->groupBy('address')
                    ->get()
                    ->each(function ($row) use (&$data) {
                        $data['offices'][$row->address] = [
                            'count' => $row->count,
                            'name' => $this->getOfficeName($row->address),
                            'id' => $row->address,
                        ];
                    });

                $data['offices'] = array_values($data['offices']);
            }

            if ($tab->counter_source) {

                $sources_id = [];

                foreach ($sources as $source) {

                    $data['sources'][$source['id']] = [
                        'count' => 0,
                        'name' => $source['name'],
                        'id' => $source['id'],
                    ];

                    $sources_id[] = $source['id'];
                }

                $query->model()
                    ->selectRaw('count(*) as count, source_id')
                    ->where('source_id', '!=', null)
                    ->reorder()
                    ->groupBy('source_id')
                    ->get()
                    ->each(function ($row) use (&$data, $sources_id) {

                        $sources_key = in_array($row->source_id, $sources_id) ? 'sources' : 'sources_hide';

                        if (!isset($data[$sources_key]))
                            $data[$sources_key] = [];

                        $data[$sources_key][$row->source_id] = [
                            'count' => $row->count,
                            'name' => $this->getSourceName($row->source_id),
                            'id' => $row->source_id,
                        ];
                    });

                $data['sources'] = array_values($data['sources']);
            }

            $counter[$tab->id] = $data;
        }

        return $counter ?? [];
    }

    /**
     * Выводит список источников, которые необходимы для вывода счетчика
     * 
     * @return  array
     */
    public function getActiveSources()
    {
        return RequestsSource::where('show_counter', 1)
            ->orderBy('name')
            ->get()
            ->map(function ($row) {

                $this->get_source_name[$row->id] = $row->name;

                return $row->only('id', 'name');
            })
            ->toArray();
    }

    /**
     * Выводит наименование источника
     * 
     * @param  int $id
     * @return null|string
     */
    public function getSourceName($id)
    {
        if (!empty($this->get_source_name[$id]))
            return $this->get_source_name[$id];

        $row = RequestsSource::find($id);

        return $this->get_source_name[$id] = $row->name ?? null;
    }

    /**
     * Формирует данные для грфиков
     * 
     * @param  array $data
     * @param  array $story
     * @return array
     */
    public function chartCounter($data, $story = [])
    {
        $charts = [];

        foreach ($data as $key => $tab) {

            $row = array_merge($tab, [
                'column' => collect([]),
                'line' => collect([]),
            ]);

            $this->pushRowData($row, $tab);

            if (isset($story[$key])) {

                foreach ($story[$key] as $date => $tab) {
                    $this->pushRowData($row, $tab, $date);
                }
            }

            $charts[$key] = $row;
        }

        foreach ($charts as &$row) {

            $row['column'] = $row['column']->sortBy('date')->values()->all();
            $row['line'] = $row['line']->sortBy('date')->values()->all();
        }

        return array_values($charts);
    }

    /**
     * Заполняет элемент данными графика
     * 
     * @param  array $row Данные для графика
     * @param  array $tab Данные счетчика
     * @param  string $date Дата подсчета
     * @return array
     */
    public function pushRowData(&$row, $tab, $date = null)
    {
        $date = $date ?: now()->format("Y-m-d");

        $row['column']->push([
            'date' => $date,
            'count' => $tab['count'],
        ]);

        if (isset($tab['sources'])) {

            foreach ($tab['sources'] as $source) {

                $row['line']->push([
                    'date' => $date,
                    'count' => $source['count'],
                    'name' => $source['name'],
                ]);
            }
        }

        if (isset($tab['offices'])) {

            foreach ($tab['offices'] as $source) {

                $row['line']->push([
                    'date' => $date,
                    'count' => $source['count'],
                    'name' => $source['name'],
                ]);
            }
        }

        return $row;
    }

    /**
     * Подсчитывает информацию о клиентах
     * 
     * @param  null|string $date
     * @return array
     */
    public function getClientsData($date = null)
    {
        $date = now()->create($date ?: now()->format("Y-m-d"));
        $between = [
            $date->copy()->startOfDay()->format("Y-m-d H:i:s"),
            $date->copy()->endOfDay()->format("Y-m-d H:i:s"),
        ];

        /** Количество обращений */
        $data['queries'] = RequestsClientsQuery::whereBetween('created_at', $between)
            ->count();

        /** Количество клиентов */
        $data['clients'] = RequestsClientsQuery::select('client_id')
            ->whereBetween('created_at', $between)
            ->distinct()
            ->count();

        /** Количество новых клиентов */
        $data['clients_new'] = RequestsClientsQuery::select('client_id')
            ->whereBetween('requests_clients_queries.created_at', $between)
            ->join('requests_clients', function ($join) use ($between) {
                $join->on('requests_clients.id', '=', 'client_id')
                    ->whereBetween('requests_clients.created_at', $between);
            })
            ->distinct()
            ->count();

        /** Количество новых заявок */
        $data['requests'] = RequestsClientsQuery::select('request_id')
            ->whereBetween('created_at', $between)
            ->distinct()
            ->count();

        /** Количество новых заявок */
        $data['requests_new'] = RequestsClientsQuery::select('request_id')
            ->whereBetween('requests_clients_queries.created_at', $between)
            ->join('requests_rows', function ($join) use ($between) {
                $join->on('requests_rows.id', '=', 'request_id')
                    ->where('requests_rows.deleted_at', null)
                    ->whereBetween('requests_rows.created_at', $between);
            })
            ->distinct()
            ->count();

        /** Источники и ресурсы */
        RequestsClientsQuery::select('source_id', 'resource_id')
            ->whereBetween('created_at', $between)
            ->distinct()
            ->get()
            ->each(function ($row) use (&$sources) {
                $sources[(int) $row->source_id][] = (int) $row->resource_id;
            });

        $data['sources'] = $sources ?? [];

        return $data;
    }
}
