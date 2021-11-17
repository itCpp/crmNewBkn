<?php

namespace App\Http\Controllers\Offices;

use App\Http\Controllers\Controller;
use App\Models\Office;
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
        if ($request->forSetting)
            return self::getOfficeForSetting($request);

        if (!$row = Office::find($request->id))
            return response()->json(['message' => "Данные выбранного офиса не найдены"], 400);

        return response()->json([
            'office' => $row,
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
        if (!$row = Office::find($request->id))
            return response()->json(['message' => "Данные выбранного офиса не найдены"], 400);

        return response()->json([
            'office' => $row,
        ]);
    }
}
