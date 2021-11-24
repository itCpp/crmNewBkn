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
        $rows = RequestsQueue::where('done_at', null)
            ->get()
            ->map(function ($row) {
                $row->request_data = parent::decrypt($row->request_data);
                return $row->toArray();
            });

        return response()->json([
            'queues' => $rows,
        ]);
    }
}
