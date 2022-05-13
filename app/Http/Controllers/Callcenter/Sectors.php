<?php

namespace App\Http\Controllers\Callcenter;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings;
use App\Models\CallcenterSector;
use App\Models\CallcenterSectorsAutoSetSource;
use App\Models\Incomings\CallsSectorSetting;
use App\Models\RequestsSource;
use App\Models\SettingsGlobal;
use Illuminate\Http\Request;

class Sectors extends Controller
{
    /**
     * Данные одного сектора
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getSector(Request $request)
    {
        if (!$row = CallcenterSector::find($request->sector))
            return response()->json(['message' => "Данные сектора не найдены"], 400);

        $row->source_selects = CallcenterSectorsAutoSetSource::where('sector_id', $row->id)
            ->get()
            ->map(function ($row) {
                return $row->source_id;
            })
            ->toArray();

        if ($request->getSources) {
            $row->sources = RequestsSource::orderBy('name')
                ->orderBy('actual_list', "DESC")
                ->get();
        }

        $auto_set = (int) (new Settings)->AUTOSET_SECTOR_NEW_REQUEST;
        $row->auto_set = (int) ($auto_set == $row->id);

        return response()->json([
            'auto_set' => $auto_set,
            'sector' => $row,
        ]);
    }

    /**
     * Изменение данных сектора
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
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
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function createNewSector(Request $request)
    {
        $row = CallcenterSector::create([
            'name' => $request->name,
            'comment' => $request->comment,
            'active' => (int) $request->active,
        ]);

        $auto_set = self::setGlobalSettingAutoSetSectorNewRequest($row->id);
        $row->auto_set = (int) ($auto_set > 0);

        parent::logData($request, $row); # Логирование изменений

        return response()->json([
            'sector' => $row,
        ]);
    }

    /**
     * Изменение данных сектора
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function changeDataSector(Request $request)
    {
        if (!$row = CallcenterSector::find($request->id))
            return response()->json(['message' => "Данные сектора не найдены"], 400);

        $row->name = $request->name;
        $row->comment = $request->comment;
        $row->active = (int) $request->active;

        $row->save();

        $auto_set = self::setGlobalSettingAutoSetSectorNewRequest($row->id);
        $row->auto_set = (int) ($auto_set > 0);

        parent::logData($request, $row); # Логирование изменений

        // Обновление данных настроек распределения сектора
        if ($setting = CallsSectorSetting::find($row->id)) {

            if (
                $setting->name != $row->name
                or $setting->comment != $row->comment
            ) {

                $setting->name = $row->name;
                $setting->comment = $row->comment;

                $setting->save();

                parent::logData($request, $setting);
            }
        }

        return response()->json([
            'sector' => $row,
        ]);
    }

    /**
     * Устанавливает настройку автоматического назначения сектора заявки
     * 
     * @param  int $id
     * @return int
     */
    public static function setGlobalSettingAutoSetSectorNewRequest($id)
    {
        $key = "AUTOSET_SECTOR_NEW_REQUEST";
        $settings = new Settings;
        $auto_set = $settings->$key;

        if ((bool) request()->auto_set and $auto_set != $id) {
            $row = $settings->setOrCreate($key, $id);
        } else if (!(bool) request()->auto_set and $auto_set == $id) {
            $id = 0;
            $row = $settings->setOrCreate($key, $id);
        }

        if (isset($row)) {
            $row->value = $id;
            parent::logData(request(), $row);
            return $id;
        }

        return 0;
    }
}
