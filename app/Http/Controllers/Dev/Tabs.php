<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Requests\RequestsQuery;
use App\Models\RequestsRow;
use App\Models\Status;
use App\Models\Tab;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Tabs extends Controller
{
    /**
     * Список разрешенных выражений для запроса
     * 
     * @var array
     */
    protected static $whereList = [
        "where",
        "orWhere",
        "whereNotBetween",
        "whereBetween",
        "whereIn",
        "whereNotIn",
        "whereNull",
        "whereNotNull",
        "orWhereNull",
        "orWhereNotNull",
        "whereDate",
        "whereMonth",
        "whereDay",
        "whereYear",
        "whereTime",
        "whereColumn",
    ];

    /**
     * Вывод всех вкладок
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTabs(Request $request)
    {
        $rows = Tab::orderBy('position')
            ->get()
            ->map(function ($row) {
                return $this->serializeRow($row);
            })
            ->toArray();

        return response()->json([
            'tabs' => $rows,
        ]);
    }

    /**
     * Обработка строки
     * 
     * @param \App\Models\Tab $row
     * @return array
     */
    public function serializeRow(Tab $row)
    {
        $row->date_types = $row->date_types ?: [];
        $row->request_all = $row->request_all ?: "my";
        $row->statuses = $row->statuses ?: [];
        $row->statuses_not = $row->statuses_not ?: [];

        return $row->toArray();
    }

    /**
     * Создание новой вкладки
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTab(Request $request)
    {
        $request->validate([
            'name' => "required",
        ], [
            'name.required' => "Необходимо указать наименование вкладки",
        ]);

        $tab = Tab::create([
            'name' => $request->name,
            'name_title' => $request->name_title,
            'request_all_permit' => 1,
            'position' => Tab::count(),
        ]);

        $this->logData($request, $tab);

        return response()->json([
            'tab' => $this->serializeRow($tab),
        ]);
    }

    /**
     * Вывод данных одной вкладки
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTab(Request $request)
    {
        if (!$tab = Tab::find($request->id))
            return response()->json(['message' => "Данные по вкладке не найдены"], 400);

        if ($request->getColumns)
            $columns = RequestsRow::getColumnsList();

        if ($request->getStatuses)
            $statuses = Status::all();

        return response()->json([
            'tab' => $this->serializeRow($tab),
            'columns' => $columns ?? null,
            'statuses' => $statuses ?? null,
        ]);
    }

    /**
     * Изменение данных вкладки
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveTab(Request $request)
    {
        if (!$tab = Tab::find($request->id))
            return response()->json(['message' => "Вкладка не найдена"], 400);

        // Проверка допустимых выражений
        if ($request->where_settings) {

            foreach ($request->where_settings as $key => $query) {
                if ($query['where'] != "whereFunction") {
                    if (!in_array($query['where'], self::$whereList))
                        $errors['where_settings'][$key]['where'][] = "Недопустимое выражение {$query['where']}";
                }
            }
        }

        if ($errors ?? null) {
            return response()->json([
                'message' => "Имеются ошибки",
                'errors' => $errors,
            ], 422);
        }

        $tab->name = $request->name;
        $tab->name_title = $request->name_title;
        $tab->where_settings = $this->fixNullValueWhere($request->where_settings ?: []);
        $tab->order_by_settings = $request->order_by_settings;
        $tab->request_all = $request->request_all;
        $tab->request_all_permit = $request->request_all_permit;
        $tab->date_view = $request->date_view;
        $tab->date_types = $request->date_types ?: null;
        $tab->statuses = is_array($request->statuses) ? $request->statuses : [];
        $tab->statuses_not = is_array($request->statuses_not) ? $request->statuses_not : [];
        $tab->counter_offices = (boolean) $request->counter_offices;
        $tab->counter_source = (boolean) $request->counter_source;

        $tab->save();

        $this->logData($request, $tab);

        return response()->json([
            'tab' => $this->serializeRow($tab),
        ]);
    }

    /**
     * Изменяет нулевое значение на пустую строку
     * 
     * @param array $data
     * @return array
     */
    public function fixNullValueWhere($data = [])
    {
        foreach ($data as &$row) {

            if (is_array($row['attr'] ?? null)) {

                foreach ($row['attr'] as &$attr) {

                    if (in_array('value', array_keys(is_array($attr) ? $attr : []))) {

                        if ($attr['value'] === null)
                            $attr['value'] = "";
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Вывод сформированного запроса по динамическому конструктору
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSql(Request $request)
    {
        if (!$request->tab = Tab::find($request->id))
            return response()->json(['message' => "Вкладка не найдена"], 400);

        $creator = new RequestsQuery($request);
        $litle = $creator->toSql();

        $creator = (new RequestsQuery($request))->where();
        $query = $creator->toSql();
        $bindings = collect($creator->getBindings())->map(function ($row) {
            return is_string($row) ? "'{$row}'" : $row;
        })->toArray();

        $full = Str::replaceArray('?', $bindings, $query);

        return response()->json([
            'message' => $litle,
            'full' => $full,
            'tab' => $this->serializeRow($request->tab),
        ]);
    }

    /**
     * Вывод списка значений для конструктора запросов
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getListWhereIn(Request $request)
    {
        if ($request->preset == "status")
            $list = Statuses::getListStatuses($request);
        elseif ($request->preset == "sources")
            $list = Sources::getListSources($request);
        elseif ($request->preset == "resources")
            $list = Sources::getListResources($request);

        return response()->json([
            'list' => $list ?? [],
            'preset' => $request->preset,
        ]);
    }

    /**
     * Установка порядка вывода вкладок
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function tabsPosition(Request $request)
    {
        foreach ($request->all() as $tab) {
            Tab::where('id', $tab['id'])->limit(1)
                ->update(['position' => (int) $tab['position']]);
        }

        return response()->json([
            'message' => "Порядок расположения обновлен",
        ]);
    }

    /**
     * Формирование массива выбранных статусов
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function setTabStatus(Request $request)
    {
        if (!$status = Status::find($request->id))
            return response()->json(['message' => "Выбранный статус был уже удален или не существует"], 400);

        if (!$tab = Tab::find($request->tab))
            return response()->json(['message' => "Вкладка не найдена"], 400);

        $statuses = $tab->statuses ?: [];

        // Добавление статуса во вкладку
        if ($request->checked and !in_array($status->id, $statuses))
            $statuses[] = $status->id;
        // Удаление статуса из вкладки
        else if (!$request->checked and in_array($status->id, $statuses))
            $statuses = array_diff($statuses, [$status->id]);

        $tab->statuses = count($statuses) ? $statuses : null;

        $tab->save();

        parent::logData($request, $tab); // Логирование

        return response()->json([
            'tab' => $tab,
        ]);
    }
}
