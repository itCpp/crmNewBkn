<?php

namespace App\Http\Controllers\Offices;

use App\Http\Controllers\Controller;
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
        return response()->json([
            'office' => $request->row,
            'statuses' => Status::select('id', 'name')->orderBy('name')->get(),
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

        $row->save();

        Log::log($request, $row);

        return response([
            'office' => $row,
        ]);
    }
}
