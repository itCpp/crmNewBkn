<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Status;

class Statuses extends Controller
{
    
    /**
     * Список всех статусов
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Response
     */
    public static function getStatuses(Request $request)
    {

        $statuses = Status::all();

        return \Response::json([
            'statuses' => $statuses ?? [],
        ]);

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

        if (count($errors)) {
            return \Response::json([
                'message' => "Имеются ошибки при заполнении данных",
                'errors' => $errors,
            ], 422);
        }

        $status = Status::create([
            'name' => $request->name,
            'zeroing' => $request->zeroing ? 1 : 0,
            'zeroing_data' => $request->zeroing_data,
        ]);

        return \Response::json([
            'status' => $status,
        ]);

    }

}
