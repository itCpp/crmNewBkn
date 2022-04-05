<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SecondCalls\SecondCalls;
use App\Http\Controllers\Sms\Sms;
use App\Http\Controllers\Testing\MyTests;
use App\Models\RequestsQueue;
use Illuminate\Http\Request;

class Counters extends Controller
{
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
}
