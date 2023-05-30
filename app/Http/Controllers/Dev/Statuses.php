<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Status;
use Illuminate\Http\Request;

class Statuses extends Controller
{
    /**
     * Алгоритным обнуления заявок
     * 
     * @var array
     */
    public static $algorithms = [
        ['name' => "xHour", "option" => true],
        ['name' => "nextDay", "option" => false],
        ['name' => "xDays", "option" => true],
    ];

    /**
     * Все настройки статуса
     * 
     * @var array<string, mixed>
     */
    public static $settings = [
        'auto_change_id' => Status::class, # Идентификатор статуса для смены
        'auto_change_minutes' => "integer", # Время просроченного статуса
        'auto_change_column' => ['event_at', 'created_at'], # Колонки учета времени
    ];

    /**
     * Список всех статусов
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getStatuses(Request $request)
    {
        $statuses = Status::orderBy('name')
            ->get()
            ->map(function ($row) {
                return self::serializeRow($row);
            })
            ->toArray();

        return response()->json([
            'statuses' => $statuses,
        ]);
    }

    /**
     * Подготовка строки со статусом для вывода
     * 
     * @param  \App\Models\Status
     * @return array
     */
    public static function serializeRow(Status $row)
    {
        $row->zeroing_data = !is_array($row->zeroing_data)
            ? json_decode($row->zeroing_data, true)
            : $row->zeroing_data;

        return $row->toArray();
    }

    /**
     * Список статусов для списка выбора
     * 
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public static function getListStatuses(Request $request)
    {
        return Status::orderBy('name')
            ->get()
            ->map(function ($row) {
                return [
                    'key' => $row->id,
                    'value' => $row->id,
                    'text' => $row->name,
                ];
            });
    }

    /**
     * Изменение данных статуса
     * 
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function saveStatus(Request $request)
    {
        $request->validate([
            'name' => "required|max:50",
        ]);

        if (!$status = Status::find($request->id)) {

            if ($request->id)
                return response()->json(['message' => "Данные о статусе не найдены"], 400);

            $request->validate([
                'name' => "required|unique:statuses|max:50",
            ]);

            $status = new Status;
        }

        if ($request->zeroing == 1) {

            $errors = [];

            $zeroing = $request->zeroing_data ?? [];

            $algorithm = $zeroing['algorithm'] ?? null;
            $algorithm_option = $zeroing['algorithm_option'] ?? null;

            $time_created = $zeroing['time_created'] ?? null;
            $time_event = $zeroing['time_event'] ?? null;
            $time_updated = $zeroing['time_updated'] ?? null;

            if (!$algorithm)
                $errors['algorithm'][] = "Необходимо выбрать лагоритм обнуления";

            if (in_array($algorithm, ["xHour", "xDays"]) and !$algorithm_option)
                $errors['algorithm_option'][] = "Нужно указать значение к алгоритму";

            if (!$time_created and !$time_event and !$time_updated)
                $errors['time'][] = "Необходимо выбрать время учета";

            if (count($errors)) {
                return response()->json([
                    'message' => "Имеются ошибки при заполнении данных",
                    'errors' => $errors,
                ], 422);
            }
        }

        $status->name = $request->name;
        $status->zeroing = $request->zeroing ? 1 : 0;
        $status->event_time = $request->event_time ? 1 : 0;
        $status->zeroing_data = is_array($zeroing ?? null) ? json_encode($zeroing) : null;
        $status->theme = $request->theme;

        $settings = [];

        if (is_array($request->settings)) {

            foreach ($request->settings as $key => $value) {
                if (isset(self::$settings[$key])) {
                    $settings[$key] = $value;
                }
            }
        }

        $status->settings = $settings;

        $status->save();

        parent::logData($request, $status);

        return response()->json([
            'status' => self::serializeRow($status),
        ]);
    }

    /**
     * Создание нового статуса
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function createStatus(Request $request)
    {
        return self::saveStatus($request);
    }

    /**
     * Вывод данных одного статуса
     * 
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getStatusData(Request $request)
    {
        if (!$status = Status::find($request->id))
            return response()->json(['message' => "Данные о статусе не найдены"], 400);

        return response()->json([
            'status' => self::serializeRow($status),
            'settings' => self::getAllSettings($status),
        ]);
    }

    /**
     * Вывод данных по алгоритму обнуления
     * 
     * @param  string $name
     * @return null|array
     */
    public static function findAlgorithm($name = "")
    {
        foreach (self::$algorithms as $algorithm) {
            if ($algorithm['name'] == $name)
                return $algorithm;
        }

        return null;
    }

    /**
     * Смена темы оформления строки заявки
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function setStatuseTheme(Request $request)
    {
        if (!$status = Status::find($request->id))
            return response()->json(['message' => "Данные о статусе не найдены"], 400);

        $status->theme = $request->theme;
        $status->save();

        parent::logData($request, $status);

        return response()->json([
            'status' => self::serializeRow($status),
        ]);
    }

    /**
     * Вывод доступных настроек
     * 
     * @param  \App\Models\Status $row
     * @return array
     */
    public static function getAllSettings($row)
    {
        foreach (self::$settings as $setting_key => $setting) {

            $type = gettype($setting);
            $data = null;

            if ($type == "string" and class_exists($setting)) {
                $type = "array";
                $data = (new $setting)->get()->map(function ($row) {

                    if ($row->name)
                        $text = $row->name;

                    return [
                        'key' => $row->id,
                        'text' => $text ?? $row->id,
                        'value' => $row->id,
                    ];
                })->toArray();
            } else if ($type == "array") {

                foreach ($setting as $key => $value) {
                    $data[] = [
                        'key' => $key,
                        'text' => $value,
                        'value' => $value,
                    ];
                }
            }

            $settings[] = [
                'name' => $setting_key,
                'type' => $type,
                'data' => $data ?: null,
            ];
        }

        return $settings ?? [];
    }
}
