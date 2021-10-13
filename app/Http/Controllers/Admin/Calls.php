<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IncomingCall;
use App\Models\IncomingCallsToSource;
use App\Models\RequestsSourcesResource;
use Illuminate\Http\Request;

class Calls extends Controller
{
    /**
     * Загрузка страницы журнала звонков
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function start(Request $request)
    {
        $data = IncomingCall::orderBy('id', "DESC")
            ->limit(40)
            ->get();

        foreach ($data as $call) {
            $call->phone = parent::decrypt($call->phone);
            $call->phone = parent::checkPhone($call->phone, 2);
            $calls[] = $call->toArray();
        }

        return response()->json([
            'calls' => $calls ?? [],
        ]);
    }

    /**
     * Вывод списка слушателей входящих звонков
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function getIncomingCallExtensions(Request $request)
    {
        $extensions = IncomingCallsToSource::all();

        return response()->json([
            'extensions' => $extensions,
        ]);
    }

    /**
     * Вывод данных одного слушателя
     * Для создание нового слушателя потребуются данные источников
     * поэтому слушатель может вернуть пустой массив
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function getIncomingCallExtension(Request $request)
    {
        if ($request->id !== true) {

            $extension = IncomingCallsToSource::find($request->id);

            if (!$extension)
                return response()->json(['message' => "Слушатель с id#{$request->id} не найден"], 400);
        }

        // Список используемых номеров телефона
        $resources = RequestsSourcesResource::where([
            ['source_id', '!=', null],
            ['type', 'phone']
        ])
            ->get()
            ->map(function ($row) {
                $row->source = $row->source;
                return $row;
            });

        return response()->json([
            'extension' => $extension ?? [],
            'resources' => $resources,
        ]);
    }

    /**
     * Сохранение данных или создание нового слушателя
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function saveIncpmingExtension(Request $request)
    {
        $errors = [];

        if (!$request->phone)
            $errors['extension'][] = "Не указан сип аккаунт слушателя";

        if (!$request->phone)
            $errors['phone'][] = "Не указан номер телефона источника";

        if ($request->phone and !$phone = parent::checkPhone($request->phone, 1))
            $errors['phone'][] = "Неправильно указан номер телефона источника";

        if (count($errors)) {
            return response()->json([
                'message' => "Имеются ошибки в заполненных данных",
                'errors' => $errors,
            ], 400);
        }

        if (!$request->id and IncomingCallsToSource::where('extension', $request->extension)->count()) {
            return response()->json([
                'message' => "Данный сип аккаунт уже используется",
                'errors' => [
                    'extensions' => [
                        "Данный сип аккаунт уже используется",
                    ],
                ]
            ]);
        }

        if (!$extension = IncomingCallsToSource::find($request->id) and $request->id)
            return response()->json(['message' => "Слушатель с id#{$request->id} не найден"], 400);

        if (!$extension)
            $extension = new IncomingCallsToSource;

        $extension->extension = $request->extension;
        $extension->phone = $phone;
        $extension->on_work = (int) $request->on_work;
        $extension->comment = $request->comment;

        $extension->save();

        \App\Models\Log::log($request, $extension);

        $resource = RequestsSourcesResource::where([
            ['val', $phone],
            ['type', 'phone']
        ])->first();

        if (!$resource) {
            $alert = "Ресурс источника с номером телефона {$phone} не создан, не забудьте это сделать";
        } elseif (!$resource->source_id) {
            $alert = "Указанный номер телефона не используется в источниках";
        }

        return response()->json([
            'extension' => $extension,
            'resource' => $resource,
            'alert' => $alert ?? null,
        ]);
    }
}
