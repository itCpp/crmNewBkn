<?php

namespace App\Http\Controllers\Base;

use App\Http\Controllers\Crm\Calls as CrmCalls;
use Illuminate\Http\Request;

class Calls
{
    /**
     * Выводит список аудиозаписей по заявке
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        $calls = new CrmCalls;
        $ids = explode(",", $request->id);

        if (env('NEW_CRM_OFF', true)) {
            request()->merge([
                'checkFromOld' => true,
            ]);
        }

        $hashs = [];

        foreach (array_unique($ids) as $id) {

            request()->merge([
                'request' => $id,
                'hidePhone' => true,
            ]);

            $phones = $calls->getPhone($request);
            $hashs = [...$hashs, ...$calls->getPhonesHashs($phones)];
        }

        $rows = collect($calls->getCalls($hashs))
            ->map(function ($row) {

                if ($row['type'] == "out") {
                    $row['call_direction'] = "outbound";
                    $row['caller_destination'] = $row['extension'];
                    $row['calling'] = $row['phone'];
                } else {
                    $row['call_direction'] = $row['type'];
                    $row['caller_destination'] = $row['phone'];
                    $row['calling'] = $row['extension'];
                }

                $row['date'] = date("d.m.Y в H:i", strtotime($row['call_at']));
                $row['sec'] = $row['duration'] * 1000;
                $row['newName'] = basename($row['url']);

                return $row;
            })
            ->toArray();

        return response()->json([
            'id' => $ids,
            'calls' => $rows,
        ]);
    }
}
