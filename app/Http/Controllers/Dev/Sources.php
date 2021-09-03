<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RequestsSource;
use App\Models\RequestsSourcesResource;

class Sources extends Controller
{

    /**
     * Список источников с ресурсами
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public static function getSources(Request $request)
    {

        $sources = RequestsSource::orderBy('id', "DESC")->get();

        foreach ($sources as &$source) {

            $source->resurces = $source->resources;

        }
        
        return \Response::json([
            'sources' => $sources,
        ]);

    }

    /**
     * Создание нового источника
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public static function createSource(Request $request)
    {

        $source = RequestsSource::create();

        \App\Models\Log::log($request, $source);

        return \Response::json([
            'source' => $source,
        ]);

    }

}
