<?php

namespace App\Http\Controllers\Ratings;

use App\Http\Controllers\Controller;
use App\Models\Callcenter;
use App\Models\RatingCallcenterSelected;
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

        $selected = $request->user()->callcenter_id;

        if ($all) {
            $callcenter_selected = RatingCallcenterSelected::where('user_id', $request->user()->id)->first();
            $selected = $callcenter_selected->callcenter_id ?? null;
        }

        return response()->json([
            'all_access' => $all,
            'callcenter' => $selected,
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

    /**
     * Выводит данные для графика рейтинга за последние 30 дней
     * 
     * @param \Illumiante\Http\Request $request
     * @return array
     */
    public static function getChartData(Request $request)
    {
        return (new Charts)($request);
    }
}
