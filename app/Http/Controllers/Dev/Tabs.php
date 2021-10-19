<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\RequestsRow;
use App\Models\Status;
use App\Models\Tab;
use Illuminate\Http\Request;

class Tabs extends Controller
{
    /**
     * Вывод всех вкладок
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function getTabs(Request $request)
    {
        foreach (Tab::orderBy('position')->get() as $tab) {

            // $tab->where_settings = json_decode($tab->where_settings);

            $tabs[] = $tab;
        }

        return response()->json([
            'tabs' => $tabs ?? [],
        ]);
    }

    /**
     * Создание новой вкладки
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function createTab(Request $request)
    {
        $errors = [];

        if (!$request->name)
            $errors['name'][] = "Необходимо указать наименование вкладки";

        if (count($errors)) {
            return response()->json([
                'message' => "Имеются ошибки данных",
                'errors' => $errors,
            ], 422);
        }

        $tab = Tab::create([
            'name' => $request->name,
            'name_title' => $request->name_title,
            'position' => Tab::count(),
        ]);

        parent::logData($request, $tab);

        return response()->json([
            'tab' => $tab,
        ]);
    }

    /**
     * Вывод данных одной вкладки
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function getTab(Request $request)
    {
        if (!$tab = Tab::find($request->id))
            return response()->json(['message' => "Данные по вкладке не найдены"], 400);

        if ($request->getColumns)
            $columns = RequestsRow::getColumnsList();

        if ($request->getStatuses)
            $statuses = Status::all();

        return response()->json([
            'tab' => $tab,
            'columns' => $columns ?? null,
            'statuses' => $statuses ?? null,
        ]);
    }

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
     * Изменение данных вкладки
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function saveTab(Request $request)
    {
        if (!$tab = Tab::find($request->id))
            return response()->json(['message' => "Информация по вкладке не обнаружена, обновите страницу и повторите запрос"], 400);

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
        $tab->where_settings = $request->where_settings;
        $tab->order_by_settings = $request->order_by_settings;
        $tab->request_all = $request->request_all;
        $tab->request_all_permit = $request->request_all_permit;
        $tab->date_view = $request->date_view;
        $tab->date_types = $request->date_types ?: null;

        $tab->save();

        parent::logData($request, $tab);

        return response()->json([
            'tab' => $tab,
        ]);
    }

    /**
     * Вывод сформированного запроса по динамическому конструктору
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function getSql(Request $request)
    {
        if (!$tab = Tab::find($request->id))
            return response()->json(['message' => "Информация по вкладке не обнаружена, обновите страницу и повторите запрос"], 400);

        // $tab->where_settings = json_decode($tab->where_settings, true);

        // \DB::enableQueryLog();

        $request->where = $request->where ?? $tab->where_settings;
        $request->orderBy = $request->orderBy ?? $tab->order_by_settings;

        $params = array_merge(
            $request->where ?? [],
            $request->orderBy ?? []
        );

        $model = RequestsRow::setWhere($params);
        $query = $model->toSql();

        // $model->limit(1)->get();

        return response()->json([
            'message' => $query,
            // 'where_settings' => $tab->where_settings,
            // 'log' => \DB::getQueryLog()[0] ?? null,
            'tab' => $tab,
        ]);
    }

    /**
     * Вывод списка значений для конструктора запросов
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
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
     * @return response
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
     * @return response
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
