<?php

namespace App\Http\Controllers\Ratings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Ratings extends Controller
{
    /**
     * Вывод основного рейтинга колл-центров
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getCallCenters(Request $request)
    {
        return response()->json(
            (new CallCenters($request))->get()
        );
    }
}
