<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\RequestsSource;
use Illuminate\Http\Request;

class RequestAddManual extends Controller
{
    /**
     * Вывод данных для создания нвой заявки вручную
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function addShow(Request $request)
    {
        // Доступные источники
        $sources = RequestsSource::where('actual_list', 1)->get();

        return response()->json([
            'sources' => $sources,
            'offices' => Office::where('active', 1)->get(),
            'cities' => \App\Http\Controllers\Infos\Cities::$data, // Список городов
            'themes' => \App\Http\Controllers\Infos\Themes::$data, // Список тем
        ]);
    }

    /**
     * Создание новой заявки вручную
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function create(Request $request)
    {
        $errors = [];

        if (!$request->query_type)
            $errors['query_type'][] = "Обязательно укажите тип заявки";

        if (!$request->phone)
            $errors['phone'][] = "Обязательно укажите номер телефона";

        if (!$phone = parent::checkPhone($request->phone))
            $errors['phone'][] = "неправильно указан номер телефона";

        if (!$request->source)
            $errors['source'][] = "Обязательно укажите источник обращения";

        if (count($errors)) {
            return response()->json([
                'message' => "Имеются ошибки в данных",
                'errors' => $errors,
            ], 400);
        }

        // Создание нового объекта запроса для добавления заявки
        $add_request = new Request(
            query: [
                'manual' => true, // Идентфикатор ручного создания заявки
                'query_type' => $request->query_type, // Тип заявки
                'phone' => $phone,
                'client_name' => $request->client_name,
                'source' => $request->source,
                'comment_main' => $request->comment,
                'comment_first' => $request->comment_first,
                'theme' => $request->theme,
                'city' => $request->region,
            ],
            server: [
                'REMOTE_ADDR' => $request->ip(),
                'HTTP_USER_AGENT' => $request->userAgent(),
            ]
        );

        $add_request->setUserResolver(function () use ($request) {
            return $request->user();
        });

        $add = new AddRequest($add_request);
        $data = $add->add();

        return response()->json([
            'message' => "Заявка создана",
            'id' => $data['requestId'] ?? null,
            'add' => $data,
        ]);
    }
}
