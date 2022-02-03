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
        $counter = [];

        foreach ($request->tabs as $tab) {
            $request->tab = $tab;

            $query = new RequestsQuery($request);
            $count = $query->count();

            $key = "tab{$tab->id}";

            $counter[$key] = [
                'id' => $tab->id,
                'name' => $tab->name,
                'count' => $count,
            ];
        }

        $permits = $request->user()->getListPermits([
            'queues_access',
            'sms_access',
            'second_calls_access'
        ]);

        // Счетчик очереди
        if ($permits->queues_access)
            $counter['queue'] = self::getQueueCounter($request);

        if ($permits->sms_access)
            $counter['sms'] = Sms::getCounterNewSms($request);

        if ($permits->second_calls_access)
            $counter['secondcalls'] = SecondCalls::getCounterNewSecondCalls($request);

        /** Счетчик незавершенных тестирований сотрудника */
        $counter['tests'] = MyTests::countTestings($request->user()->pin, $request->user()->old_pin);

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
