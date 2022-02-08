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
     * Список всех статусов
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
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
     * @param \App\Models\Status
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
     * @param \Illuminate\Http\Request $request
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
     * @param \Illuminate\Http\Request
     * @return response
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

        $status->save();

        return response()->json([
            'status' => self::serializeRow($status),
        ]);
    }

    /**
     * Создание нового статуса
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function createStatus(Request $request)
    {
        return self::saveStatus($request);
    }

    /**
     * Вывод данных одного статуса
     * 
     * @param \Illuminate\Http\Request
     * @return response
     */
    public static function getStatusData(Request $request)
    {
        if (!$status = Status::find($request->id))
            return response()->json(['message' => "Данные о статусе не найдены"], 400);

        return response()->json([
            'status' => self::serializeRow($status),
        ]);
    }

    /**
     * Вывод данных по алгоритму обнуления
     * 
     * @var string $name
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
     * @param \Illuminate\Http\Request $request
     * @return response
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
}
