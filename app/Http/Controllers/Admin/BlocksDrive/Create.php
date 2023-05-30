<?php

namespace App\Http\Controllers\Admin\BlocksDrive;

use App\Http\Controllers\Controller;
use App\Models\BlockIp;
use Illuminate\Http\Request;

class Create extends Controller
{
    /**
     * Создание блокировки
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        if (!$request->start and !filter_var($request->ip, FILTER_VALIDATE_IP)) {
            return response()->json([
                'message' => "Неправильный IP адрес"
            ], 400);
        } else if (
            !filter_var($request->ip, FILTER_VALIDATE_IP)
            and !filter_var($request->start, FILTER_VALIDATE_IP)
            and !filter_var($request->stop, FILTER_VALIDATE_IP)
        ) {
            return response()->json([
                'message' => "Неправильный диапазон IP адресов"
            ], 400);
        }

        if (BlockIp::where('ip', $request->ip)->first()) {
            return response()->json([
                'message' => "Данная конфигурация блокировки уже существует"
            ], 400);
        }

        $row = new BlockIp;
        $row->ip = $request->ip;

        $row->hostname = gethostbyaddr(
            filter_var($request->ip, FILTER_VALIDATE_IP) ? $request->ip : $request->start
        );

        if ($request->start and $request->stop) {

            $row->is_period = 1;
            $row->period_data = [
                'start' => $request->start,
                'stop' => $request->stop,
                'startLong' => ip2long($request->start),
                'stopLong' => ip2long($request->stop),
            ];
        }

        $row->save();

        $this->logData($request, $row);

        $blockIps = new BlockIps;
        $blockIps->pushIpRow($row);

        return response()->json([
            'row' => $blockIps->setResultRow($blockIps->rows[$row->ip]),
        ]);
    }
}
