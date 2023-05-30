<?php

namespace App\Http\Controllers\Base;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Ratings\CallCenters;
use Illuminate\Http\Request;

class Ratings extends Controller
{
    /**
     * Вывод рейтинга колл-центра
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function callcenter(Request $request)
    {
        return response()->json(
            (new CallCenters($request))->get()
        );
    }
}
