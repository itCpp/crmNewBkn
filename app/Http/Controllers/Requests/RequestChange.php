<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RequestsRow;
use App\Models\RequestsStory;
use App\Models\Status;

class RequestChange extends Controller
{
    
    /**
     * Изменение данных заявки
     * 
     * @param \Illuminate\Http\Request
     * @return response
     */
    public static function save(Request $request)
    {

        if (!$row = RequestsRow::find($request->id))
            return response()->json(['message' => "Заявка не найдена"], 400);

        $errors = [];

        if ($request->status_id AND !$status = Status::find($request->status_id))
            $errors['status_id'][] = "Выбранный статус не существует";

        // Проверка времени для события
        if (($status->event_time ?? null) AND (!$request->event_date OR !$request->event_time)) {

            if (!$request->event_date)
                $errors['event_date'][] = "Необходимо указать дату";

            if (!$request->event_time)
                $errors['event_time'][] = "Необходимо указать время";

        }

        if (count($errors)) {
            return response()->json([
                'message' => "Имеются ошибки в заполнении",
                'errors' => $errors,
            ], 400);
        }

        $row->client_name = $request->client_name; // ФИО клиента
        $row->theme = $request->theme; // Тематика обращения
        $row->region = $request->region; // Город клиента

        $row->comment = $request->comment; // Общий комментарий
        $row->comment_urist = $request->comment_urist; // Комментарий юристу

        if ($request->event_date)
            $row->event_at = $request->event_date;

        if ($row->event_at AND $request->event_time)
            $row->event_at = date("Y-m-d {$request->event_time}:00", strtotime($row->event_at));

        $row->uplift = 0; // Убрать из необработанных с каким-либо статусом

        $row->save();

        // Логирование изменений заявки
        RequestsStory::write($request, $row);

        return response()->json([
            'request' => Requests::getRequestRow($row),
        ]);

    }

}
