<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

use App\Models\Tab;
use App\Models\RequestsRow;
use App\Models\Status;

class Requests extends Controller
{

    /**
     * Список разрешений
     * 
     * @var array
     */
    public static $permits = [];

    /**
     * Список проверки разрешений для заявки
     * 
     * @var array
     */
    public static $permitsList = [
        'requests_add', // Может добавлять новую заявку
        'requests_edit', // Может редактировать заявку
        'request_set_null_status', // Может устанавливать статус необработано
    ];
    
    /**
     * Вывод заявок
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function get(Request $request)
    {

        if (!$request->tab = Tab::find($request->tabId))
            return response()->json(['message' => "Выбрана несуществующая вкладка"], 400);

        // Проверка разрешения на просмотр вкладки
        if (!$request->__user->canTab($request->tab->id))
            return response()->json(['message' => "Доступ к вкладке ограничен"], 403);

        // Разрешения для пользователя
        Requests::$permits = $request->__user->getListPermits(Requests::$permitsList);

        $where = RequestsRow::setWhere($request->tab->where_settings ?? []);
        $data = Requests::setQuery($request, $where)->paginate(25);

        $requests = Requests::getRequests($data);

        return response()->json([
            'requests' => $requests,
            'permits' => Requests::$permits,
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
        Requests::$permits = $request->__user->getListPermits(Requests::$permitsList);

        $row = Requests::getRequestRow($row);

        $addstatus = true; // Добавить в список недоступный статус
        $statusList = $request->__user->getStatusesList(); // Список доступных статусов

        // Преобразование списка для вывода
        $statuses = $statusList->map(function ($status) use ($addstatus, $row) {

            if ($status->id == $row->status_id)
                $addstatus = false;

            return [
                'id' => $status->id,
                'text' => $status->name,
                'value' => $status->id,
            ];

        });

        // Добавление недоступного для пользователя статуса
        if ($addstatus) {

            $blockstatus = Status::find($row->status_id);

            $statuses[] = [
                'id' => $blockstatus->id ?? $row->status_id,
                'text' => $blockstatus->name ?? "Неизвестный статус",
                'value' => $blockstatus->id ?? $row->status_id,
                'disabled' => true,
            ];
        }

        return response()->json([
            'request' => $row,
            'permits' => Requests::$permits,
            'statuses' => $statuses,
            'cities' => \App\Http\Controllers\Infos\Cities::$data, // Список городов
            'themes' => \App\Http\Controllers\Infos\Themes::$data, // Список тем
        ]);

    }

    /**
     * Дополнение запроса на вывод данных
     * 
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\RequestsRow   $data
     * @return \App\Models\RequestsRow
     */
    public static function setQuery(Request $request, $data)
    {

        return $data;

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

        $row->date_create = date("d.m.Y H:i", strtotime($row->created_at));
        $row->date_uplift = $row->uplift_at ? date("d.m.Y H:i", strtotime($row->uplift_at)) : null;
        $row->date_event = $row->event_at ? date("d.m.Y H:i", strtotime($row->event_at)) : null;

        $row->event_date = $row->event_at ? date("Y-m-d", strtotime($row->event_at)) : null;
        $row->event_time = $row->event_at ? date("H:i", strtotime($row->event_at)) : null;
        $row->event_time = $row->event_time !== "00:00" ? $row->event_time : null;

        // Данные по номерам телефона
        $row->clients = $row->clients()->get()->map(function ($client) {
            return (object) [
                'id' => $client->id,
                'phone' => Crypt::decryptString($client->phone),
            ];
        });

        $row->source; // Источник заявки
        $row->status; // Статус заявки

        $row->permits = Requests::$permits;

        return (object) $row->toArray();

    }

}
