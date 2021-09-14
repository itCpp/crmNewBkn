<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Tab;
use App\Models\RequestsRow;

class Tabs extends Controller
{
    
    /**
     * Вывод всех вкладок
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Response
     */
    public static function getTabs(Request $request)
    {

        foreach (Tab::all() as $tab) {

            $tab->where_settings = json_decode($tab->where_settings);

            $tabs[] = $tab;

        }

        return \Response::json([
            'tabs' => $tabs ?? [],
        ]);
        
    }

    /**
     * Создание новой вкладки
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Response
     */
    public static function createTab(Request $request)
    {

        $errors = [];

        if (!$request->name)
            $errors['name'][] = "Необходимо указать наименование вкладки";

        if (count($errors)) {
            return \Response::json([
                'message' => "Имеются ошибки данных",
                'errors' => $errors,
            ], 422);
        }

        $tab = Tab::create([
            'name' => $request->name,
            'name_title' => $request->name_title,
        ]);

        return \Response::json([
            'tab' => $tab,
        ]);

    }

    /**
     * Вывод данных одной вкладки
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Response
     */
    public static function getTab(Request $request)
    {

        if (!$tab = Tab::find($request->id))
            return \Response::json(['message' => "Данные по вкладке не найдены"], 400);

        $tab->where_settings = json_decode($tab->where_settings);

        if ($request->getColumns)
            $columns = RequestsRow::getColumnsList();

        return \Response::json([
            'tab' => $tab,
            'columns' => $columns ?? null,
        ]);

    }

}
