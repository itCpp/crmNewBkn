<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dates;
use App\Models\Office;
use App\Models\RequestsRow;
use App\Models\RequestsRowsView;
use App\Models\RequestsStoryPin;
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
     * @return \Illuminate\Http\JsonResponse
     */
    public static function get(Request $request)
    {
        if ($request->tabId === null)
            return response()->json(['message' => "Выберите вкладку"], 400);

        if (!$request->tab = Tab::find($request->tabId))
            $request->tab = new Tab;

        $request->page = $request->page ? (int) $request->page : null;

        // Проверка разрешения на просмотр вкладки
        if ($request->tab->id and !$request->user()->canTab($request->tab->id))
            return response()->json(['message' => "Доступ к вкладке ограничен"], 403);

        // Разрешения для пользователя
        RequestStart::$permits = $request->user()->getListPermits(RequestStart::$permitsList);

        $dates = new Dates($request->period[0] ?? null, $request->period[1] ?? null);
        $request->start = $dates->start;
        $request->stop = $dates->stop;

        // Формирование запроса на вывод
        $query = new RequestsQuery($request);
        $data = $query->paginate($request->limit ?? 25);

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
     * @return \Illuminate\Http\JsonResponse
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

        $view = RequestsRowsView::firstOrNew([
            'request_id' => $row->id,
            'user_id' => $request->user()->id,
        ]);

        $view->view_at = now();
        $view->save();

        $response = [
            'request' => $row,
            'permits' => RequestStart::$permits,
            'statuses' => $statuses,
            'offices' => Office::orderBy('active', 'DESC')->orderBy('name')->get(),
            'cities' => \App\Http\Controllers\Infos\Cities::$data, // Список городов
            'themes' => \App\Http\Controllers\Infos\Themes::$data, // Список тем
        ];

        if ($request->getComments)
            $response['comments'] = Comments::getComments($request);

        return response()->json($response);
    }

    /**
     * Запрос на вывод данных для добавления заявки оператору
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getRowForTab(Request $request)
    {
        if (!$request->tab = Tab::find($request->tabId))
            return response()->json(['message' => "Информация о вкладке не найдена"], 400);

        if (!$request->user()->canTab($request->tab->id))
            return response()->json(['message' => "Доступ к вкладке ограничен"], 403);

        // Разрешения для пользователя по заявкам
        RequestStart::$permits = $request->user()->getListPermits(RequestStart::$permitsList);

        // Формирование запроса на вывод
        $query = new RequestsQuery($request);
        $row = $query->where('id', $request->id)->first();

        return response()->json([
            'row' => $row ? self::getRequestRow($row) : null,
        ]);
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
            $requests[] = self::getRequestRow($row);

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
        $row->clients = self::getClientPhones($row, request()->user()->can('clients_show_phone'));

        $row->source; # Источник заявки
        $row->status; # Вывод данных о статусе
        $row->office; # Вывод данных по офису
        $row->sector; # Вывод данных по сектору

        $row->view_at = RequestsRowsView::where([
            'user_id' => request()->user()->id,
            'request_id' => $row->id,
        ])->first()->view_at ?? null;

        $row->updated = $row->view_at < $row->updated_at;

        return (object) $row->toArray();
    }

    /**
     * Вывод номеров телефона клиента
     * 
     * @param \App\Models\RequestsRow $row
     * @param null|bool $permit Флаг разрешения на вывод номера
     * @return array
     */
    public static function getClientPhones(RequestsRow $row, $permit = false)
    {
        return $row->clients()->get()->map(function ($client) use ($permit) {

            $phone = Crypt::decryptString($client->phone);
            $type = parent::KEY_PHONE_HIDDEN; // Ключ модификации номера телефона

            if ($permit)
                $type = parent::KEY_PHONE_SHOW;

            return (object) [
                'id' => $client->id,
                'phone' => parent::checkPhone($phone, $type),
                'hidden' => (bool) !$permit,
            ];
        });
    }

    /**
     * Вывод новых заявок для личной страницы
     * 
     * @param string|int $pin
     * @return array
     */
    public static function getNewRequests($pin)
    {
        $requests = RequestsStoryPin::distinct()
            ->select('request_id', 'requests_story_pins.created_at')
            ->join('requests_rows', function ($join) use ($pin) {
                $join->on('requests_rows.id', '=', 'requests_story_pins.request_id')
                    ->where('requests_rows.pin', $pin);
            })
            ->where([
                ['new_pin', $pin],
                ['requests_rows.deleted_at', null],
            ])
            ->orderBy('requests_story_pins.created_at', 'DESC')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                return $row->request_id;
            })
            ->toArray();

        request()->user()->can('sss', 'sasdasdasd');

        return RequestsRow::whereIn('id', $requests)
            ->get()
            ->map(function ($row) {
                return self::getRequestRow($row);
            });
    }
}
