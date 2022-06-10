<?php

namespace App\Http\Controllers\Offices;

use App\Http\Controllers\Controller;
use App\Models\Base\Office as BaseOffice;
use App\Models\CallcenterSector;
use App\Models\Gate;
use App\Models\Office;
use App\Models\Status;
use Illuminate\Http\Request;

class Offices extends Controller
{
    /**
     * Вывод списка офисов
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getOffices(Request $request)
    {
        $offices = Office::orderBy('active', "DESC")->orderBy('name')->get();

        return response()->json([
            'offices' => $offices,
        ]);
    }

    /**
     * Вывод данных офиса
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getOffice(Request $request)
    {
        $request->id = $request->id === true ? 0 : $request->id;

        if (!$request->row = Office::find($request->id) and !$request->forSetting)
            return response()->json(['message' => "Данные выбранного офиса не найдены"], 400);

        if ($request->forSetting)
            return self::getOfficeForSetting($request);

        return response()->json([
            'office' => $request->row,
        ]);
    }

    /**
     * Вывод данных офиса для его настроек
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getOfficeForSetting(Request $request)
    {
        $sectors = CallcenterSector::where('callcenter_id', '!=', null)
            ->orderBy('callcenter_id')
            ->orderBy('name')
            ->get();

        $ids = Office::select('base_id')
            ->when((bool) ($request->row->base_id ?? false), function ($query) use ($request) {
                $query->where('base_id', '!=', $request->row->base_id);
            })
            ->where('base_id', '!=', null)
            ->get()
            ->map(function ($row) {
                return $row->base_id;
            })
            ->toArray();

        $synh_id = BaseOffice::select('oldId as value', 'name as text')
            ->whereNotIn('oldId', $ids)
            ->get();

        return response()->json([
            'office' => $request->row ?? [],
            'statuses' => Status::select('id', 'name')->orderBy('name')->get(),
            'sectors' => $sectors,
            'gates' => Gate::where('for_sms', 1)->get(),
            'synh' => $synh_id,
        ]);
    }

    /**
     * Сохранение данных офиса
     * 
     * @param  \App\Http\Controllers\Offices\OfficeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function saveOffice(OfficeRequest $request)
    {
        if ($request->id)
            return self::updateOfficeData($request);

        $row = Office::create(
            $request->only([
                'base_id',
                'active',
                'name',
                'addr',
                'address',
                'sms',
                'statuses',
                'tel',
                'settings',
            ])
        );

        parent::logData($request, $row, true);

        return response()->json([
            'office' => $row,
        ]);
    }

    /**
     * Обновление данных офиса
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function updateOfficeData(Request $request)
    {
        if (!$row = Office::find($request->id))
            return response()->json(['message' => "Данные выбранного офиса не найдены"], 400);

        $row->base_id = $request->base_id;
        $row->name = $request->name;
        $row->addr = $request->addr;
        $row->address = $request->address;
        $row->active = (int) $request->active;
        $row->sms = $request->sms;
        $row->tel = $request->tel;
        $row->statuses = $request->statuses;

        $row->save();

        parent::logData($request, $row, true);

        return response()->json([
            'office' => $row,
        ]);
    }

    /**
     * Поиск значения в массиве настроек офиса
     * 
     * @param  \App\Models\Office $office
     * @param  string $type
     * @param  null|int $gate
     * @param  null|int $sector
     * @param  string $value
     * @return mixed
     */
    public static function getSettingValue(Office $office, $type = null, $gate = null, $sector = null, $value = "value")
    {
        if (!is_array($office->settings))
            return null;

        foreach ($office->settings as $row) {
            if ($type == $row->type) {

                // Вывод значения по шлюзу
                if (!$sector and $gate and $gate == $row->gate)
                    return !empty($row->$value) ? $row->$value : null;

                // Вывод значения по сектору
                if ($sector and !$gate and $sector == $row->sector)
                    return !empty($row->$value) ? $row->$value : null;

                // Вывод значение по шлюзу и сектору
                if ($sector and $gate and $sector == $row->sector and $gate == $row->gate)
                    return !empty($row->$value) ? $row->$value : null;

                // Вывод по типу
                if (!$gate and !$sector)
                    return !empty($row->$value) ? $row->$value : null;
            }
        }

        return null;
    }
}
