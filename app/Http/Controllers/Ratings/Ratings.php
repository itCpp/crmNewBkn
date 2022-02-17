<?php

namespace App\Http\Controllers\Ratings;

use App\Http\Controllers\Controller;
use App\Models\Callcenter;
use Illuminate\Http\Request;

class Ratings extends Controller
{
    /**
     * Запуск рейтинга
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ratingStart(Request $request)
    {
        $all = $request->user()->can('rating_all_callcenters');

        if ($all) {
            $centers = Callcenter::where('active', 1)
                ->get()
                ->map(function ($row) {
                    return [
                        'value' => $row->id,
                        'text' => $row->name,
                        'key' => $row->id,
                    ];
                })
                ->toArray();
        }

        return response()->json([
            'all_access' => $all,
            'centers' => $centers ?? [],
        ]);
    }

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
