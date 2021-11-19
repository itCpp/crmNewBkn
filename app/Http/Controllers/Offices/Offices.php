<?php

namespace App\Http\Controllers\Offices;

use App\Http\Controllers\Controller;
use App\Models\CallcenterSector;
use App\Models\Gate;
use App\Models\Log;
use App\Models\Office;
use App\Models\Status;
use Illuminate\Http\Request;

class Offices extends Controller
{
    /**
     * Вывод списка офисов
     * 
     * @param Request $request
     * @return response
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
     * @param Request $request
     * @return response
     */
    public static function getOffice(Request $request)
    {
        if (!$request->row = Office::find($request->id))
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
     * @param Request $request
     * @return response
     */
    public static function getOfficeForSetting(Request $request)
    {
        $sectors = CallcenterSector::where('callcenter_id', '!=', null)
            ->orderBy('callcenter_id')
            ->orderBy('name')
            ->get();

        return response()->json([
            'office' => $request->row,
            'statuses' => Status::select('id', 'name')->orderBy('name')->get(),
            'sectors' => $sectors,
            'gates' => Gate::where('for_sms', 1)->get(),
        ]);
    }

    /**
     * Сохранение данных офиса
     * 
     * @param Request $request
     * @return response
     */
    public static function saveOffice(Request $request)
    {
        $errors = [];

        if ($request->tel and !parent::checkPhone($request->tel))
            $errors['tel'] = "Номер телефона секретаря указан неправильно";

        if (count($errors)) {
            return response()->json([
                'message' => "Имеются ошибки в данных",
                'errors' => $errors,
            ], 400);
        }

        if ($request->id)
            return self::updateOfficeData($request);

        return response()->json(['message' => "Запрос не обработан"], 400);
    }

    /**
     * Обновление данных офиса
     * 
     * @param Request $request
     * @return response
     */
    public static function updateOfficeData(Request $request)
    {
        if (!$row = Office::find($request->id))
            return response()->json(['message' => "Данные выбранного офиса не найдены"], 400);

        $row->name = $request->name;
        $row->addr = $request->addr;
        $row->address = $request->address;
        $row->active = (int) $request->active;
        $row->sms = $request->sms;
        $row->tel = $request->tel;
        $row->statuses = $request->statuses;

        $row->settings = $request->settings;

        $row->save();

        Log::log($request, $row);

        return response([
            'office' => $row,
        ]);
    }

    /**
     * Поиск значения в массиве настроек офиса
     * 
     * @param Office $office
     * @param string $type
     * @param null|int $gate
     * @param null|int $sector
     * @param string $value
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
