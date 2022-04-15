<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Jobs\Developer\RequestsSourceChangeAbbrNameJob;
use App\Models\IncomingCallsToSource;
use App\Models\Incomings\SourceExtensionsName;
use App\Models\RequestsSource;
use App\Models\RequestsSourcesResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class Sources extends Controller
{
    /**
     * Список источников с ресурсами
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Support\Facades\Response
     */
    public static function getSources(Request $request)
    {
        $sources = RequestsSource::orderBy('id', "DESC")->get();

        foreach ($sources as &$source) {
            $source->resources;
        }

        return Response::json([
            'sources' => $sources,
        ]);
    }

    /**
     * Вывод данных для настройки источника
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Support\Facades\Response
     */
    public static function getSourceData(Request $request)
    {
        if (!$source = RequestsSource::find($request->id))
            return Response::json(['message' => "Источник не найден"], 400);

        $source->resources = $source->resources;

        return Response::json([
            'source' => $source,
        ]);
    }

    /**
     * Изменение настроек источника
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Support\Facades\Response
     */
    public static function saveSourceData(Request $request)
    {
        if (!$source = RequestsSource::find($request->id))
            return Response::json(['message' => "Источник не найден"], 400);

        $abbr_name = $source->abbr_name;

        $source->actual_list = (int) $request->actual_list;
        $source->auto_done_text_queue = (int) $request->auto_done_text_queue;
        $source->show_counter = (int) $request->show_counter;
        $source->comment = $request->comment;
        $source->name = $request->name;
        $source->abbr_name = $request->abbr_name;

        $source->save();

        $source->resources = $source->resources;

        parent::logData($request, $source);

        if ($abbr_name != $source->abbr_name)
            RequestsSourceChangeAbbrNameJob::dispatch($source);

        return Response::json([
            'source' => $source,
        ]);
    }

    /**
     * Создание нового источника
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Support\Facades\Response
     */
    public static function createSource(Request $request)
    {
        $source = RequestsSource::create();
        $source->resources;

        parent::logData($request, $source);

        return Response::json([
            'source' => $source,
        ]);
    }

    /**
     * Список ресурсов для источников
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Support\Facades\Response
     */
    public static function getResources(Request $request)
    {
        foreach (RequestsSourcesResource::orderBy('id', "DESC")->get() as $resource)
            $resources[] = self::getResourceRow($resource);

        return Response::json([
            'resources' => $resources ?? [],
        ]);
    }

    /**
     * Метод вывод данных одной строки ресурса
     * 
     * @param \App\Models\RequestsSourcesResource $resource
     * @return object
     */
    public static function getResourceRow(RequestsSourcesResource $resource)
    {
        $data = (object) $resource->toArray();

        $data->source = $resource->source;

        $data->icon = $resource->type == "site" ? "world" : "phone";
        $data->date = date("d.m.Y H:i:s", strtotime($resource->created_at));

        return $data;
    }

    /**
     * Создание нового ресурса для источников
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Support\Facades\Response
     */
    public static function createResource(Request $request)
    {
        if (!$request->resource) {
            return Response::json([
                'message' => "Введите ресурс",
            ], 400);
        }

        // Источник с номером телефона
        if ($phone = self::checkPhone($request->resource)) {

            $request->phone = $phone;

            return self::createResourcePhone($request);
        }

        if ($site = self::checkSiteUrl($request->resource)) {
            $request->site = $site;
            return self::createResourceSite($request);
        }

        return Response::json([
            'message' => "Тип ресурса не определен",
        ], 400);
    }

    /**
     * Проверка домена
     * 
     * @param string $resource
     * @return false|null|string
     */
    public static function checkSiteUrl($resource)
    {
        if (filter_var($resource, FILTER_VALIDATE_URL) !== false) {

            $url = parse_url($resource);

            return $url['host'] ?? null;
        }


        if (filter_var($resource, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false)
            return $resource;

        return false;
    }

    /**
     * Создание ресурса в виде номера телефона
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Support\Facades\Response
     */
    public static function createResourcePhone($request)
    {
        if (RequestsSourcesResource::where('type', 'phone')->where('val', $request->phone)->count())
            return Response::json([
                'message' => "Телефон {$request->phone} уже добавлен в ресурсы",
            ], 400);

        $resource = RequestsSourcesResource::create([
            'source_id' => null,
            'type' => "phone",
            'val' => $request->phone,
        ]);

        parent::logData($request, $resource);

        return Response::json([
            'resource' => Sources::getResourceRow($resource),
        ]);
    }

    /**
     * Создание ресурса в виде номера телефона
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Support\Facades\Response
     */
    public static function createResourceSite($request)
    {
        if (!$request->site)
            return Response::json([
                'message' => "Имя хоста не определено",
            ], 400);

        if (RequestsSourcesResource::where('type', 'site')->where('val', $request->site)->count())
            return Response::json([
                'message' => "Сайт с доменом {$request->site} уже добавлен в ресурсы",
            ], 400);

        $resource = RequestsSourcesResource::create([
            'source_id' => null,
            'type' => "site",
            'val' => $request->site,
        ]);

        parent::logData($request, $resource);

        return Response::json([
            'resource' => Sources::getResourceRow($resource),
        ]);
    }

    /**
     * Применение источника к ресурсу
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Support\Facades\Response
     */
    public static function setResourceToSource(Request $request)
    {
        if (!$source = RequestsSource::find($request->sourceId))
            return Response::json(['message' => "Источник не найден"], 400);

        if (!$resource = RequestsSourcesResource::find($request->resourceId))
            return Response::json(['message' => "Ресурс не найден"], 400);

        $resource->source_id = $request->set ? $source->id : null;
        $resource->save();

        parent::logData($request, $resource);

        $source->resources = $source->resources()->get();

        if ($resource->type == "phone") {

            if ($call = IncomingCallsToSource::where('phone', $resource->val)->first()) {

                $extension = SourceExtensionsName::firstOrNew([
                    'extension' => $call->extension,
                ]);

                $extension->abbr_name = $request->set ? $source->abbr_name : null;
                $extension->save();
            }
        }

        return Response::json([
            'source' => $source,
            'checked' => $request->set,
        ]);
    }

    /**
     * Вывод свободных и активных ресурсов по источнику
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Support\Facades\Response
     */
    public static function getFreeResources(Request $request)
    {
        if (!$source = RequestsSource::find($request->id))
            return Response::json(['message' => "Источник не найден"], 400);

        $free = RequestsSourcesResource::where('source_id', null)->get();

        return Response::json([
            'resources' => $source->resources()->get(),
            'freeResources' => $free,
        ]);
    }

    /**
     * Вывод источников для списка выбора
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public static function getListSources(Request $request)
    {
        $rows = RequestsSource::orderBy('name');

        foreach ($rows->get() as $source) {
            $sources[] = [
                'key' => $source->id,
                'value' => $source->id,
                'text' => $source->name ?? $source->id,
            ];
        }

        return $sources ?? [];
    }

    /**
     * Вывод ресурсов для списка выбора
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public static function getListResources(Request $request)
    {
        $rows = RequestsSourcesResource::orderBy('val');

        foreach ($rows->get() as $resource) {
            $resources[] = [
                'key' => $resource->id,
                'value' => $resource->id,
                'text' => $resource->val,
            ];
        }

        return $resources ?? [];
    }
}
