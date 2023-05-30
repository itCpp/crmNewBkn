<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gate;
use Illuminate\Http\Request;

class Gates extends Controller
{
    /**
     * Вывод шлюзов
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $rows = Gate::orderBy('addr')
            ->get()
            ->map(function ($row) {
                return $this->serializeRow($row);
            })
            ->toArray();

        return response()->json([
            'rows' => $rows,
        ]);
    }

    /**
     * Преобразование строки для вывода
     * 
     * @param \App\Models\Gate $row
     * @return array
     */
    public function serializeRow(Gate $row)
    {
        $row->ami_user = $this->decrypt($row->ami_user);
        $row->ami_pass = $this->decrypt($row->ami_pass);

        $row->headers = $this->decrypt($row->headers);

        return $row->toArray();
    }

    /**
     * Вывод данных шлюза
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        if (!$row = Gate::find($request->id))
            return response()->json(['message' => "Данные шлюза не найдены"], 400);

        return response()->json([
            'row' => $this->serializeRow($row),
        ]);
    }

    /**
     *  Сохранение шлюза
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        $request->validate([
            'addr' => 'required|ip',
            'name' => "nullable|max:255",
            'ami_user' => "required|max:255",
            'ami_pass' => "required|max:255",
            'channels' => "required|int",
            'cookies' => "nullable|array",
        ]);

        if (!$row = Gate::find($request->id))
            $row = new Gate;

        $headers = $request->input('headers') ?: [];

        foreach ($request->input('cookies') as $cookie) {
            if ((bool) $cookie['name'])
                $headers['Cookie'][$cookie['name']] = $cookie['value'];
        }

        $row->addr = $request->addr;
        $row->name = $request->name;
        $row->ami_user = $this->encrypt($request->ami_user);
        $row->ami_pass = $this->encrypt($request->ami_pass);
        $row->channels = $request->channels;
        $row->check_incoming_sms = (int) $request->check_incoming_sms;
        $row->for_sms = (int) $request->for_sms;
        $row->headers = $this->encrypt($headers);

        $row->save();

        $this->logData($request, $row, true);

        return response()->json([
            'row' => $this->serializeRow($row),
        ]);
    }
}
