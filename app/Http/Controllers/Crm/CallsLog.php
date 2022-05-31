<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CallDetailRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait CallsLog
{
    /**
     * Выводит журнал вызовов
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLog(Request $request)
    {
        CallDetailRecord::orderBy('id', "DESC")
            ->paginate(40)
            ->each(function ($row) use (&$rows) {
                $rows[] = $this->logRowSerialize($row);
            });

        return response()->json([
            'rows' => $rows ?? [],
        ]);
    }

    /**
     * Формирует строку звонка
     * 
     * @param  \App\Models\CallDetailRecord $row
     * @return \App\Models\CallDetailRecord
     */
    public function logRowSerialize($row)
    {
        $row->call_at = $row->call_at ?: $row->created_at;

        if (Str::startsWith($row->path, '/'))
            $row->path = Str::replaceFirst('/', '', $row->path);

        $host = env('CALL_DETAIL_RECORDS_SERVER', 'http://localhost:8000');
        $row->url = Str::finish($host, '/') . $row->path;

        $phone = Controller::decrypt($row->phone);

        $hide_phone = 5;
        $type = (optional(request()->user())->can('clients_show_phone')) ? 2 : $hide_phone;

        $phone = Controller::checkPhone($phone, $type) ?: $phone;
        $extension = $row->extension;

        if ($row->type == "out") {
            $row->caller = $extension;
            $row->phone = $phone;
        } else {
            $row->caller = $phone;
            $row->phone = $extension;
        }

        $row->hidePhone = $type === $hide_phone;

        return $row;
    }
}
