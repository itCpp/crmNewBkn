<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\RequestsRow;
use App\Models\Status;
use App\Models\Tab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class Requests extends Controller
{
    /**
     * Вывод заявок
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function get(Request $request)
    {
        if (!$request->tab = Tab::find($request->tabId))
            $request->tab = new Tab;

        // Проверка разрешения на просмотр вкладки
        if ($request->tab->id and !$request->user()->canTab($request->tab->id))
            return response()->json(['message' => "Доступ к вкладке ограничен"], 403);

        // Разрешения для пользователя
        RequestStart::$permits = $request->user()->getListPermits(RequestStart::$permitsList);

        // Формирование запроса на вывод
        $query = new RequestsQuery($request);
        $data =  $query->paginate($request->limit ?? 25);

        $requests = self::getRequests($data);

        $next = $data->currentPage() + 1; // Следующая страница
        $pages = $data->lastPage(); // Общее количество страниц

        return response()->json([
            'requests' => $requests,
            'permits' => RequestStart::$permits,
            'total' => $data->total(), // Количество найденных строк
            'next' => $next > $pages ? null : $next,
            'pages' => $pages,
            'page' => $request->page,
        ]);
    }

    /**
     * Вывод одной строки
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function getRow(Request $request)
    {
        if (!$row = RequestsRow::find($request->id))
            return response()->json(['message' => "Заявка не найдена"], 400);

        // Поиск разрешений для заявок
        RequestStart::$permits = $request->__user->getListPermits(RequestStart::$permitsList);

        $row = Requests::getRequestRow($row); // Полные данные по заявке

        $addstatus = true; // Добавить в список недоступный статус
        $statusList = $request->__user->getStatusesList(); // Список доступных статусов

        // Преобразование списка для вывода
        $statuses = $statusList->map(function ($status) use (&$addstatus, $row) {

            if ($status->id == $row->status_id)
                $addstatus = false;

            return [
                'id' => $status->id,
                'text' => $status->name,
                'value' => $status->id,
            ];
        });

        // Добавление недоступного для пользователя статуса
        if ($addstatus and $row->status_id) {

            $blockstatus = Status::find($row->status_id);

            $statuses[] = [
                'id' => $blockstatus->id ?? $row->status_id,
                'text' => $blockstatus->name ?? "Неизвестный статус",
                'value' => $blockstatus->id ?? $row->status_id,
                'disabled' => true,
            ];
        }

        $response = [
            'request' => $row,
            'permits' => RequestStart::$permits,
            'statuses' => $statuses,
            'offices' => Office::all(),
            'cities' => \App\Http\Controllers\Infos\Cities::$data, // Список городов
            'themes' => \App\Http\Controllers\Infos\Themes::$data, // Список тем
        ];

        if ($request->getComments)
            $response['comments'] = Comments::getComments($request);

        return response()->json($response);
    }

    /**
     * Формирование запроса и вывод заявок
     * 
     * @param \App\Models\RequestsRow $data Коллекция модели
     * @return array
     */
    public static function getRequests($data)
    {
        foreach ($data as $row)
            $requests[] = Requests::getRequestRow($row);

        return $requests ?? [];
    }

    /**
     * Обработка и дополнение данными одной строки заявки
     * 
     * @param \App\Models\RequestsRow $row
     * @return object
     */
    public static function getRequestRow(RequestsRow $row)
    {
        $row->permits = RequestStart::$permits; // Разрешения пользователя

        $row->date_create = date("d.m.Y H:i", strtotime($row->created_at));
        $row->date_uplift = $row->uplift_at ? date("d.m.Y H:i", strtotime($row->uplift_at)) : null;
        $row->date_event = $row->event_at ? date("d.m.Y H:i", strtotime($row->event_at)) : null;

        if ($row->date_uplift === $row->date_create)
            $row->date_uplift = null;

        $row->event_date = $row->event_at ? date("Y-m-d", strtotime($row->event_at)) : null;
        $row->event_time = $row->event_at ? date("H:i", strtotime($row->event_at)) : null;
        $row->event_time = $row->event_time !== "00:00" ? $row->event_time : null;
        $row->event_datetime = $row->event_date && $row->event_time
            ? "{$row->event_date}T{$row->event_time}" : null;

        // Данные по номерам телефона
        $row->clients = $row->clients()->get()->map(function ($client) {
            return (object) [
                'id' => $client->id,
                'phone' => Crypt::decryptString($client->phone),
            ];
        });

        $row->source; # Источник заявки
        $row->status; # Вывод данных о статусе
        $row->office; # Вывод данных по офису
        $row->sector; # Вывод данных по сектору

        return (object) $row->toArray();
    }
}
