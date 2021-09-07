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
            $source->resources;
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
        $source->resources;

        \App\Models\Log::log($request, $source);

        return \Response::json([
            'source' => $source,
        ]);

    }

    /**
     * Список ресурсов для источников
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public static function getResources(Request $request)
    {

        foreach (RequestsSourcesResource::orderBy('id', "DESC")->get() as $resource)
            $resources[] = self::getResourceRow($resource);

        return \Response::json([
            'resources' => $resources ?? [],
        ]);

    }

    /**
     * Метод вывод данных одной строки ресурса
     * 
     * @param \App\Models\RequestsSourcesResource $resource
     * @return object
     */
    public static function getResourceRow(RequestsSourcesResource $resource) {

        $data = (object) $resource->toArray();

        $data->source = $resource->source();

        $data->icon = $resource->type == "site" ? "world" : "phone";
        $data->date = date("d.m.Y H:i:s", strtotime($resource->created_at));

        return $data;

    }

    /**
     * Создание нового ресурса для источников
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public static function createResource(Request $request)
    {

        if (!$request->resource)
            return \Response::json([
                'message' => "Введите ресурс",
            ], 400);

        if ($phone = self::checkPhone($request->resource)) {

            $request->phone = $phone;

            return self::createResourcePhone($request);

        }

        if (filter_var($request->resource, FILTER_VALIDATE_URL) !== false) {

            $request->url = parse_url($request->resource);
            $request->site = $request->url['host'] ?? null;

            return self::createResourceSite($request);

        }

        if (filter_var($request->resource, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false) {

            $request->site = $request->resource;

            return Sources::createResourceSite($request);

        }

        return \Response::json([
            'message' => "Тип ресурса не определен",
        ], 400);

    }

    /**
     * Создание ресурса в виде номера телефона
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public static function createResourcePhone($request)
    {

        if (RequestsSourcesResource::where('type', 'phone')->where('val', $request->phone)->count())
            return \Response::json([
                'message' => "Телефон {$request->phone} уже добавлен в ресурсы",
            ], 400);

        $resource = RequestsSourcesResource::create([
            'sourse_id' => null,
            'type' => "phone",
            'val' => $request->phone,
        ]);

        \App\Models\Log::log($request, $resource);

        return \Response::json([
            'resource' => Sources::getResourceRow($resource),
        ]);

    }

    /**
     * Создание ресурса в виде номера телефона
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public static function createResourceSite($request)
    {

        if (!$request->site)
            return \Response::json([
                'message' => "Имя хоста не определено",
            ], 400);

        if (RequestsSourcesResource::where('type', 'site')->where('val', $request->site)->count())
            return \Response::json([
                'message' => "Сайт с доменом {$request->site} уже добавлен в ресурсы",
            ], 400);

        $resource = RequestsSourcesResource::create([
            'sourse_id' => null,
            'type' => "site",
            'val' => $request->site,
        ]);

        \App\Models\Log::log($request, $resource);

        return \Response::json([
            'resource' => Sources::getResourceRow($resource),
        ]);

    }

}
