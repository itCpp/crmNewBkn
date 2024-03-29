<?php

namespace App\Http\Controllers\Callcenter;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings;
use App\Models\CallcenterSector;
use App\Models\CallcenterSectorsAutoSetSource;
use App\Models\Incomings\CallsSectorSetting;
use App\Models\RequestsSource;
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

            $response['sources'] = RequestsSource::orderBy('actual_list', "DESC")
                ->orderBy('name')
                ->get();
        }

        $auto_set = (int) (new Settings)->AUTOSET_SECTOR_NEW_REQUEST;
        $row->auto_set = (int) ($auto_set == $row->id);

        $response['auto_set'] = $auto_set;
        $response['sector'] = $row;

        return response()->json($response);
    }

    /**
     * Выводит количество выбранных источников для автоматического назначения
     * 
     * @param  int $id
     * @return int
     */
    public static function getCountSourceSelects($id)
    {
        return CallcenterSectorsAutoSetSource::where('sector_id', $id)->count();
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

        $row->sources = self::getCountSourceSelects($row->id);

        return response()->json([
            'auto_set' => $auto_set,
            'default_sector' => self::getDefaultSector(),
            'sector' => $row,
            'callcenter' => self::getCallcenterFromSector($row),
        ]);
    }

    /**
     * Выводит данные колл-центра
     * 
     * @param  \App\Models\CallcenterSector $row
     * @return \App\Models\Callcenter
     */
    public static function getCallcenterFromSector($row)
    {
        return Callcenters::serializeCallcenterRow($row->callcenter);
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

        $row->sources = self::getCountSourceSelects($row->id);

        return response()->json([
            'auto_set' => $auto_set,
            'default_sector' => self::getDefaultSector(),
            'sector' => $row,
            'callcenter' => self::getCallcenterFromSector($row),
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

        return $auto_set;
    }

    /**
     * Сохраняет источник для автоматической установки сектора новой заявке
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setAutoSector(Request $request)
    {
        if (!$source = RequestsSource::find($request->id))
            return response()->json(['message' => "Источник не найден"], 400);

        if (!$sector = CallcenterSector::find($request->sector))
            return response()->json(['message' => "Сектор не найден"], 400);

        if ($request->checked) {
            $select = $this->setAutoSectorTrue($sector->id, $source->id);
        } else {
            $select = $this->setAutoSectorFalse($sector->id, $source->id);
        }

        $this->logData($request, $select);

        return response()->json([
            'mesage' => "Настройка сохранена",
        ]);
    }

    /**
     * Устанавливает или изменяет идентификтаор сектора
     * 
     * @param  int $sector_id
     * @param  int $source_id
     * @return \App\Models\CallcenterSectorsAutoSetSource
     */
    public function setAutoSectorTrue($sector_id, $source_id)
    {
        $select = CallcenterSectorsAutoSetSource::firstOrNew([
            'source_id' => $source_id
        ]);

        $select->sector_id = $sector_id;
        $select->save();

        return $select;
    }

    /**
     * Удаляет значение 
     * 
     * @param  int $sector_id
     * @param  int $source_id
     * @return \App\Models\CallcenterSectorsAutoSetSource|null
     */
    public function setAutoSectorFalse($sector_id, $source_id)
    {
        $select = CallcenterSectorsAutoSetSource::where([
            ['sector_id', $sector_id],
            ['source_id', $source_id],
        ])->first();

        if ($select)
            $select->delete();

        return new CallcenterSectorsAutoSetSource;
    }

    /**
     * Выводит сектор, назначенный глобальной настройкой
     * 
     * @return \App\Models\CallcenterSector|null
     */
    public static function getDefaultSector()
    {
        if (!$id = (new Settings)->AUTOSET_SECTOR_NEW_REQUEST)
            return null;

        $sector = CallcenterSector::find($id);
        $sector->callcenter;

        return $sector;
    }
}
