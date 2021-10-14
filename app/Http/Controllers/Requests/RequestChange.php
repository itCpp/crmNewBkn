<?php

namespace App\Http\Controllers\Requests;

use App\Events\UpdateRequestRow;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\RequestsRow;
use App\Models\RequestsStory;
use App\Models\Status;
use App\Models\MoscowCity;

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

        if ($request->status_id and !$status = Status::find($request->status_id))
            $errors['status_id'][] = "Выбранный статус не существует";

        // Проверка времени для события
        if (($status->event_time ?? null) and (!$request->event_date or !$request->event_time)) {

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

        // Поиск разрешений для заявок
        RequestStart::$permits = $request->__user->getListPermits(RequestStart::$permitsList);

        $row->client_name = $request->client_name; // ФИО клиента

        $row->theme = $request->theme; // Тематика обращения
        $row->region = $request->region; // Город клиента
        $row->check_moscow = self::checkRegion($row->region); // Московский регион

        $row->comment = $request->comment; // Общий комментарий
        $row->comment_urist = $request->comment_urist; // Комментарий юристу

        if ($request->event_date)
            $row->event_at = $request->event_date;

        if ($row->event_at and $request->event_time)
            $row->event_at = date("Y-m-d {$request->event_time}:00", strtotime($row->event_at));

        $row->status_id = $request->status_id; // Статус заявки

        $row->address = $request->address; // Адрес офиса

        $row->uplift = 0; // Убрать из необработанных с каким-либо статусом

        $row->save();

        // Логирование изменений заявки
        RequestsStory::write($request, $row);

        $row = Requests::getRequestRow($row); // Полные данные по заявке

        // Отправка события об изменении заявки
        broadcast(new UpdateRequestRow($row))->toOthers();

        return response()->json([
            'request' => $row,
        ]);
    }

    /**
     * Метод проверки московского региона
     * 
     * @param string $city Наименование города
     * @return int|null
     */
    public static function checkRegion($city = null)
    {
        if (!$city)
            return null;

        if (MoscowCity::where('city', $city)->first())
            return 1;

        return 0;
    }

    /**
     * Сохранение данных заявки из отдельной ячейки на странице
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function saveCell(Request $request)
    {
        if (!$row = RequestsRow::find($request->id))
            return response()->json(['message' => "Заявка не найдена"], 400);

        if (!$request->__cell)
            return response()->json(['message' => "Невозможно сохранить изменения"], 400);

        // Поиск разрешений для заявок
        RequestStart::$permits = $request->__user->getListPermits(RequestStart::$permitsList);

        $method = "saveCell" . ucfirst($request->__cell);

        if (!method_exists(RequestChange::class, $method))
            return response()->json(['message' => "Невозможно сохранить изменения"], 400);

        $response = RequestChange::$method($request, $row);

        // Вывод ошибки
        if (!$response instanceof RequestsRow)
            return $response;

        // Логирование изменений заявки
        RequestsStory::write($request, $row);

        $row = Requests::getRequestRow($row); // Полные данные по заявке

        // Отправка события об изменении заявки
        broadcast(new UpdateRequestRow($row))->toOthers();

        return response()->json([
            'request' => $row,
        ]);
    }

    /**
     * Созранение данных из ячейки с датой
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\RequestsRow $row
     * @return \App\Models\RequestsRow
     */
    public static function saveCellDate(Request $request, RequestsRow $row)
    {
        $row->address = $request->address;
        $row->event_at = $request->event_datetime;

        $row->save();

        return $row;
    }

    /**
     * Созранение данных из ячейки с именем и городом
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\RequestsRow $row
     * @return \App\Models\RequestsRow
     */
    public static function saveCellClient(Request $request, RequestsRow $row)
    {
        $row->client_name = $request->client_name;
        $row->region = $request->region; // Город клиента
        $row->check_moscow = self::checkRegion($row->region); // Московский регион

        $row->save();

        return $row;
    }

    /**
     * Созранение данных из ячейки с темой
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\RequestsRow $row
     * @return \App\Models\RequestsRow
     */
    public static function saveCellTheme(Request $request, RequestsRow $row)
    {
        $row->theme = $request->theme;
        $row->save();

        return $row;
    }

    /**
     * Созранение данных из ячейки с комментарием секретаря
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\RequestsRow $row
     * @return \App\Models\RequestsRow
     */
    public static function saveCellCommentFirst(Request $request, RequestsRow $row)
    {
        $row->comment_first = $request->comment_first;
        $row->save();

        return $row;
    }

    /**
     * Созранение данных из ячейки с основным комментарием
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\RequestsRow $row
     * @return \App\Models\RequestsRow
     */
    public static function saveCellComment(Request $request, RequestsRow $row)
    {
        $row->comment = $request->comment;
        $row->save();

        return $row;
    }

    /**
     * Созранение данных из ячейки с комментарием для юриста
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\RequestsRow $row
     * @return \App\Models\RequestsRow
     */
    public static function saveCellCommentUrist(Request $request, RequestsRow $row)
    {
        $row->comment_urist = $request->comment_urist;
        $row->save();

        return $row;
    }
}
