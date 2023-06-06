<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Infos\Cities;
use App\Http\Controllers\Infos\Themes;
use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class Search extends Controller
{
    /**
     * Выводит информацию для окна поиска
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function info(Request $request)
    {
        $statuses = $request->user()
            ->getStatusesList()
            ->map(function ($row) {
                return $row->only('id', 'name');
            })
            ->push([
                'id' => -1,
                'name' => "Не обработана",
            ])
            ->sortBy('name')
            ->values()
            ->all();

        $sources = $request->user()
            ->getSourceList()
            ->map(function ($row) {
                return $row->only('id', 'name');
            })
            ->sortBy('name')
            ->values()
            ->all();

        return response()->json([
            'cities' => Cities::collect()->sort()->values()->all(),
            'sources' => $sources,
            'statuses' => $statuses,
            'themes' => Themes::collect()->sort()->values()->all(),
            'offices' => Office::where('active', 1)->orderBy('name')->get(),
        ]);
    }
}
