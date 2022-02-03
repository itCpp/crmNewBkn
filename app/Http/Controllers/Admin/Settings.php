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
                $row->type = $this->getType($row->type);

                $value = $row->value;
                settype($value, $row->type);

                $row->value = $value;

                return $row;
            })
            ->toArray();

        return response()->json([
            'settings' => $settings,
        ]);
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
}
