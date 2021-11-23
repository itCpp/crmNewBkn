<?php

namespace App\Http\Controllers\Queues;

use App\Http\Controllers\Controller;
use App\Models\RequestsQueue;
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
        return response()->json([
            'queues' => [],
        ]);
    }
}
