<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallcenterSector;
use App\Models\Log;
use App\Models\Incomings\CallsSectorSetting;
use Illuminate\Http\Request;

class DistributionCalls extends Controller
{
    /**
     * Вывод настройки распределения заказов
     * 
     * @param Request $request
     * @return response
     */
    public static function getDistributionCalls(Request $request)
    {
        $rows = CallsSectorSetting::where('active', 1)->get();
        $sectors = CallcenterSector::where('active', 1)->get();

        foreach ($rows as $key => $row)
            $keys[$row->id] = $key;

        foreach ($sectors as &$sector) {
            $sector->distribition_active = isset($keys[$sector->id]) ? $rows[$keys[$sector->id]] : null;
        }

        return response()->json([
            'rows' => $rows->toArray(),
            'sectors' => $sectors->toArray(),
        ]);
    }

    /**
     * Определние настроек единичного выбора
     * 
     * @param Request $request
     * @return response
     */
    public static function distributionSetOnly(Request $request)
    {
        if ($request->value === 1)
            return self::setDistributionCountQueue($request);

        if (strripos($request->value, "only_") !== false)
            return self::setDistributionOnlySector($request);

        return response()->json(['message' => "Выбран неактивный сектор или произошла неизвестная ошибка"], 400);
    }

    /**
     * Включение распределения между секторами
     * 
     * @param Request $request
     * @param bool $data Флаг возврата данных
     * @return response|null
     */
    public static function setDistributionCountQueue(Request $request, $data = false)
    {
        $rows = CallsSectorSetting::where('only_queue', 1)->get();

        foreach ($rows as $row) {
            $row->only_queue = 0;
            $row->save();

            Log::log($request, $row);
        }

        if ($data)
            return $rows;

        return self::getDistributionCalls($request);
    }

    /**
     * Установка одного сектора
     * 
     * @param Request $request
     * @return response
     */
    public static function setDistributionOnlySector(Request $request)
    {
        $id = str_replace("only_", "", $request->value);

        if (!$row = CallsSectorSetting::find($id))
            return response()->json(['message' => "Сектор не найден"], 400);

        self::setDistributionCountQueue($request, true);

        $row->only_queue = 1;
        $row->save();

        Log::log($request, $row);

        return self::getDistributionCalls($request);
    }

    /**
     * Сохранение значений распределения звонков
     * 
     * @param Request $request
     * @return response
     */
    public static function distributionSetCountQueue(Request $request)
    {
        $data = $request->all();

        $rows = CallsSectorSetting::where(function ($query) use ($data) {
            foreach ($data as $id => $count) {
                $query->orWhere('id', $id);
            }
        })->get();

        foreach ($rows as $row) {
            $row->count_change_queue = !empty($data[$row->id]) ? $data[$row->id] : $row->count_change_queue;
            $row->save();

            Log::log($request, $row);
        }

        return self::getDistributionCalls($request);
    }

    /**
     * Включение сектора в распределение звонков
     * 
     * @param Request $request
     * @return response
     */
    public static function setSectorDistribution(Request $request)
    {
        if (!$row = CallcenterSector::find($request->id))
            return response()->json(['message' => "Сектор не найден или уже отключен"], 400);

        if (!$distribution = CallsSectorSetting::find($row->id)) {
            $distribution = new CallsSectorSetting;
            
            $distribution->id = $row->id;
            $distribution->name = $row->name;
            $distribution->comment = $row->comment;
            $distribution->count_change_queue = 1;
        }

        $distribution->active = $request->checked ? 1 : 0;
        $distribution->save();

        Log::log($request, $distribution);

        return self::getDistributionCalls($request);
    }
}
