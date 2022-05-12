<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Offices\OfficesTrait;
use App\Http\Controllers\SecondCalls\SecondCalls;
use App\Http\Controllers\Sms\Sms;
use App\Http\Controllers\Testing\MyTests;
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
        ];

        foreach ($request->tabs as $tab) {

            $step = microtime(true);
            $request->tab = $tab;

            $query = new RequestsQuery($request);
            $count = $query->count();

            $key = "tab{$tab->id}";

            $counter[$key] = [
                'id' => $tab->id,
                'name' => $tab->name,
                'count' => $count,
            ];

            $counter['timecode'][$key] = microtime(true) - $step;
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

        $counter['timecode']['stop'] = microtime(true) - $start;

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
     */
    public function getCounterPage(Request $request)
    {
        $story = [];

        RequestsCounterStory::where('counter_date', '>=', now()->subDays(30))
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

        $counter = $this->getCounterTabsData($request->user()->getAllTabs());

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

            if ($fide and $tab->counter_hide_page)
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
}
