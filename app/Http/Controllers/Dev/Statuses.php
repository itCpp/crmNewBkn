<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Status;

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
     * @return \Response
     */
    public static function getStatuses(Request $request)
    {

        $statuses = Status::orderBy('id', "DESC")->get();

        foreach ($statuses as &$status) {
            $status->zeroing_data = json_decode($status->zeroing_data);
        }

        return \Response::json([
            'statuses' => $statuses ?? [],
        ]);

    }

    /**
     * Список статусов для списка выбора
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public static function getListStatuses(Request $request)
    {

        $rows = Status::orderBy('name');

        foreach ($rows->get() as $status) {
            $statuses[] = [
                'key' => $status->id,
                'value' => $status->id,
                'text' => $status->name,
            ];
        }

        return $statuses ?? [];

    }

    /**
     * Создание нового статуса
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Response
     */
    public static function createStatus(Request $request)
    {

        $errors = [];

        if (!$request->name)
            $errors['name'][] = "Необходимо указать наименование статуса";

        if ($request->zeroing == 1) {

            $zeroing = $request->zeroing_data ?? [];

            $algorithm = $zeroing['algorithm'] ?? null;
            $algorithm_option = $zeroing['algorithm_option'] ?? null;

            $time_created = $zeroing['time_created'] ?? null;
            $time_event = $zeroing['time_event'] ?? null;
            $time_updated = $zeroing['time_updated'] ?? null;

            if (!$algorithm)
                $errors['algorithm'][] = "Необходимо выбрать лагоритм обнуления";

            if (in_array($algorithm, ["xHour", "xDays"]) AND !$algorithm_option)
                $errors['algorithm_option'][] = "Нужно указать значение к алгоритму";

            if (!$time_created AND !$time_event AND !$time_updated)
                $errors['time'][] = "Необходимо выбрать время учета";

        }

        if (!$request->id AND Status::where('name', $request->name)->count())
            $errors['name'][] = "Это наименование уже используется";

        if (count($errors)) {
            return \Response::json([
                'message' => "Имеются ошибки при заполнении данных",
                'errors' => $errors,
            ], 422);
        }

        if ($request->__status) {

            $request->__status->name = $request->name;
            $request->__status->zeroing = $request->zeroing ? 1 : 0;
            $request->__status->zeroing_data = $request->zeroing_data;

            $request->__status->save();
            $status = $request->__status;

        }
        else {
            $status = Status::create([
                'name' => $request->name,
                'zeroing' => $request->zeroing ? 1 : 0,
                'zeroing_data' => $request->zeroing_data,
            ]);
        }

        \App\Models\Log::log($request, $status);

        return \Response::json([
            'status' => $status,
        ]);

    }

    /**
     * Вывод данных одного статуса
     * 
     * @param \Illuminate\Http\Request
     * @return \Response
     */
    public static function getStatusData(Request $request)
    {

        if (!$status = Status::find($request->id))
            return \Response::json(['message' => "Данные о статусе не найдены"], 400);

        $status->zeroing_data = json_decode($status->zeroing_data);

        return \Response::json([
            'status' => $status,
        ]);

    }

    /**
     * Изменение данных статуса
     * 
     * @param \Illuminate\Http\Request
     * @return \Response
     */
    public static function saveStatus(Request $request)
    {

        if (!$status = Status::find($request->id))
            return \Response::json(['message' => "Данные о статусе не найдены"], 400);

        $request->__status = $status;

        return Statuses::createStatus($request);

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

}
