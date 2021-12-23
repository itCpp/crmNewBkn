<?php

namespace App\Http\Controllers\Queues;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Queues\QueueProcessings;
use App\Models\RequestsQueue;
use Illuminate\Http\Request;

class Queues extends Controller
{
    /**
     * Проверенные имена хостов определенных ip
     *  
     * @var array
     */
    public static $hostnames = [];

    /**
     * Вывод очереди
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getQueues(Request $request)
    {
        $show_phone = $request->user()->can('clients_show_phone');

        $rows = RequestsQueue::where('done_type', null)
            ->get()
            ->map(function ($row) use ($show_phone) {
                $row->hostname = self::getHostName($row->ip);

                return self::modifyRow($row, $show_phone);
            });

        return response()->json([
            'queues' => $rows,
        ]);
    }

    /**
     * Получение имени хоста
     * 
     * @param string $ip
     * @return string|null
     */
    public static function getHostName($ip)
    {
        if (!empty(self::$hostnames[$ip]))
            return self::$hostnames[$ip];

        return self::$hostnames[$ip] = gethostbyaddr($ip);
    } 

    /**
     * Преобразование строки очереди
     * 
     * @param \App\Models\RequestsQueue $row
     * @param boolean $show_phone
     * @return array
     */
    public static function modifyRow($row, $show_phone = false)
    {
        $request_data = (array) parent::decrypt($row->request_data);

        if (isset($request_data['phone']))
            $request_data['phone'] = parent::displayPhoneNumber($request_data['phone'], $show_phone);

        $row->phone = $request_data['phone'] ?? null;
        $row->name = $request_data['client_name'] ?? null;
        $row->comment = $request_data['comment'] ?? null;

        $row->request_data = $request_data;

        $row->hostname = self::getHostName($row->ip);

        return $row->toArray();
    }

    /**
     * Решение по очереди
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function done(Request $request)
    {
        if (!$row = RequestsQueue::find($request->create ?: $request->drop))
            return response()->json(['message' => "Очередь не найдена"], 400);

        if ($request->create) {
            $row->done_type = 1;
            $added = (new QueueProcessings($row))->add();
        }
        else if ($request->drop)
            $row->done_type = 2;

        $row->done_at = now();
        $row->done_pin = $request->user()->pin;

        $row->save();

        return response()->json([
            'queue' => self::modifyRow($row, $request->user()->can('clients_show_phone')),
            'added' => $added ?? null,
        ]);
    }
}
