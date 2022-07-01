<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\UserSetting;
use Illuminate\Http\Request;

class Settings extends Controller
{
    /**
     * Установка настройки
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function set(Request $request)
    {
        $row = UserSetting::firstOrCreate(['user_id' => $request->user()->id]);

        $name = $request->input('name');
        $value = $request->input('value');

        if (!isset($row->toArray()[$name]))
            return response()->json(['message' => "Данная настройка не предусмотрена"], 400);

        $row->$name = $value;
        $row->save();
        
        return response()->json([
            'settings' => $row->toArray(),
            'name' => $name,
            'value' => $value,
        ]);
    }
}
