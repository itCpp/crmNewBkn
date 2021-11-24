<?php

namespace App\Http\Controllers\Queues;

use App\Http\Controllers\Controller;
use App\Models\RequestsQueue;
use App\Models\SettingsQueuesDatabase;
use Illuminate\Http\Request;

class Queues extends Controller
{
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
                $request_data = (array) parent::decrypt($row->request_data);

                if (isset($request_data['phone']))
                    $request_data['phone'] = parent::displayPhoneNumber($request_data['phone'], $show_phone);

                $row->phone = $request_data['phone'] ?? null;
                $row->name = $request_data['client_name'] ?? null;
                $row->comment = $request_data['comment'] ?? null;

                $row->request_data = $request_data;

                return $row->toArray();
            });

        return response()->json([
            'queues' => $rows,
        ]);
    }
}
