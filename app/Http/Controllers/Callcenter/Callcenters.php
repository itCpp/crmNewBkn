<?php

namespace App\Http\Controllers\Callcenter;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings;
use App\Models\Callcenter;
use App\Models\CallcenterSectorsAutoSetSource;
use Illuminate\Http\Request;

class Callcenters extends Controller
{
    /**
     * Вывод списка колл-центорв
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getCallcenters(Request $request)
    {
        $rows = Callcenter::get();

        foreach ($rows as &$row) {
            $row = self::serializeCallcenterRow($row);
        }

        return response()->json([
            'callcenters' => $rows,
            'sector_default' => Sectors::getDefaultSector(),
        ]);
    }

    /**
     * Формирует строку колл-центра для вывода
     * 
     * @param  \App\Models\Callcenter $row
     * @return \App\Models\Callcenter
     */
    public static function serializeCallcenterRow($row)
    {
        $row->sectorCount = 0;
        $row->sectorCountActive = 0;

        $row->sectors->each(function ($item) use ($row) {
            $row->sectorCount++;
            $row->sectorCountActive += $item->active;
        });

        return $row;
    }

    /**
     * Вывод данных одного колл-центра
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getCallcenter(Request $request)
    {
        if (!$row = Callcenter::find($request->id))
            return response()->json(['message' => "Данные колл-центра не найдены"], 400);

        $row->sectors = $row->sectros;

        return response()->json([
            'callcenter' => $row,
        ]);
    }

    /**
     * Сохранение данных колл-центра
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function saveCallcenter(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        if (!$row = Callcenter::find($request->id) and $request->id)
            return response()->json(['message' => "Данные колл-центра не найдены"], 400);

        if (!$row)
            $row = new Callcenter;

        $row->name = $request->name;
        $row->comment = $request->comment;
        $row->active = (int) $request->active;

        $row->save();

        parent::logData($request, $row);

        return response()->json([
            'callcenter' => self::serializeCallcenterRow($row),
        ]);
    }

    /**
     * Вывод списка секторов
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getCallcenterSectors(Request $request)
    {
        if (!$row = Callcenter::find($request->id))
            return response()->json(['message' => "Сектор по колл-центру не найдены"], 400);

        return response()->json([
            'auto_set' => (new Settings)->AUTOSET_SECTOR_NEW_REQUEST,
            'sectors' => $row->sectors->map(function ($row) {

                $row->sources = CallcenterSectorsAutoSetSource::where('sector_id', $row->id)->count();

                return $row;
            }),
        ]);
    }
}
