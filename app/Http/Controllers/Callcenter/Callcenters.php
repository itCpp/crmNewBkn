<?php

namespace App\Http\Controllers\Callcenter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Callcenter;
use App\Models\CallcenterSector;

class Callcenters extends Controller
{
    
    /**
     * Вывод списка колл-центорв
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function getCallcenters(Request $request) {

        $rows = Callcenter::get();

        foreach ($rows as &$row) {
            $row->sectorCount = $row->sectors()->count();
        }

        return response()->json([
            'callcenters' => $rows,
        ]);

    }

    /**
     * Вывод списка секторов
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function getCallcenterSectors(Request $request) {

        if (!$row = Callcenter::find($request->id))
            return response()->json(['message' => "Сектор по колл-центру не найдены"], 400);

        return response()->json([
            'sectors' => $row->sectors,
        ]);

    }

}
