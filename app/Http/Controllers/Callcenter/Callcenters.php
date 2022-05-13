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
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function getCallcenters(Request $request)
    {
        $rows = Callcenter::get();

        foreach ($rows as &$row) {
            $row->sectorCount = $row->sectors()->count();
        }

        return response()->json([
            'callcenters' => $rows,
        ]);
    }

    /**
     * Вывод данных одного колл-центра
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
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
     * @param \Illuminate\Http\Request $request
     * @return response
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
        $row->active = 1;

        $row->save();

        return response()->json([
            'callcenter' => $row,
        ]);
    }

    /**
     * Вывод списка секторов
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
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
