<?php

namespace App\Http\Controllers\Callcenter;

use App\Http\Controllers\Controller;
use App\Models\CallcenterSector;
use App\Models\Log;
use App\Models\Incomings\CallsSectorSetting;
use Illuminate\Http\Request;

class Sectors extends Controller
{
    /**
     * Данные одного сектора
     * 
     * @param Request $request
     * @return response
     */
    public static function getSector(Request $request)
    {
        if (!$row = CallcenterSector::find($request->sector))
            return response()->json(['message' => "Данные сектора не найдены"], 400);

        return response()->json([
            'sector' => $row,
        ]);
    }

    /**
     * Изменение данных сектора
     * 
     * @param Request $request
     * @return response
     */
    public static function saveSector(Request $request)
    {
        if (!$request->name) {
            return response()->json([
                'message' => "Данные заполнены неправильно",
                'errors' => [
                    'name' => [
                        'Укажите наименование сектора'
                    ]
                ]
            ], 400);
        }

        if (!$request->id)
            return self::createNewSector($request);
        
        if ($request->id)
            return self::changeDataSector($request);

        return response()->json(['message' => "Ошибка запроса"], 400);
    }

    /**
     * Создание нового сектора
     * 
     * @param Request $request
     * @return response
     */
    public static function createNewSector(Request $request)
    {
        $row = CallcenterSector::create([
            'name' => $request->name,
            'comment' => $request->comment,
            'active' => (int) $request->active,
        ]);

        return response()->json([
            'sector' => $row,
        ]);
    }

    /**
     * Изменение данных сектора
     * 
     * @param Request $request
     * @return response
     */
    public static function changeDataSector(Request $request)
    {
        if (!$row = CallcenterSector::find($request->id))
            return response()->json(['message' => "Данные сектора не найдены"], 400);

        $row->name = $request->name;
        $row->comment = $request->comment;
        $row->active = (int) $request->active;

        $row->save();

        Log::log($request, $row); # Логирование изменений

        // Обновление данных настроек распределения сектора
        if ($setting = CallsSectorSetting::find($row->id)) {

            $setting->name = $row->name;
            $setting->comment = $row->comment;

            $setting->save();

            Log::log($request, $setting);

        }

        return response()->json([
            'sector' => $row,
        ]);
    }
}
