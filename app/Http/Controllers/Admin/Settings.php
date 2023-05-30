<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings as ControllersSettings;
use App\Models\SettingsGlobal;
use Illuminate\Http\Request;

class Settings extends Controller
{
    /**
     * Вывод глобальных настроек
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $settings = SettingsGlobal::all()
            ->map(function ($row) {
                return $this->serializeRow($row);
            })
            ->toArray();

        return response()->json([
            'settings' => $settings,
        ]);
    }

    /**
     * Преобразование строки
     * 
     * @param \App\Models\SettingsGlobal $row
     * @return array
     */
    public function serializeRow(SettingsGlobal $row)
    {
        $row->type = $this->getType($row->type);
        $row->value = $this->setType($row->value, $row->type);

        return $row->toarray();
    }

    /**
     * Определение типа переменной
     * 
     * @param null|string $type
     * @return string
     */
    public function getType($type = null)
    {
        return in_array($type, ControllersSettings::$types) ? $type : "boolean";
    }

    /**
     * Изменяет тип переменной
     * 
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    public static function setType($value, $type)
    {
        settype($value, $type);

        return $value;
    }

    /**
     * Применение настройки
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function set(Request $request)
    {
        if (!$row = SettingsGlobal::find($request->id))
            return response(['message' => "Настройка не найдена в базе данных"], 400);

        if (!in_array($request->type, ControllersSettings::$types))
            return response(['message' => "Недопустимый тип переменной"], 400);

        $row->type = $request->type;
        $row->value = $request->value;

        $row->save();

        $this->logData($request, $row);

        return response()->json([
            'setting' => $this->serializeRow($row),
        ]);
    }
}
