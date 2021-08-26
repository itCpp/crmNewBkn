<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Permission;

class Permissions extends Controller
{
    
    /**
     * Вывод всех прав
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function getAllPermits(Request $request) {

        $permits = Permission::all();

        return response()->json([
            'permits' => $permits,
        ]);

    }

    /**
     * Создание нового или изменение старого правила
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function savePermit(Request $request) {

        $permit_valid = "required|regex:/^[a-zA-Z_]+$/i";

        if (!$request->edit)
            $permit_valid .= "|unique:App\Models\Permission";

        $validate = $request->validate([
            'permission' => $permit_valid,
        ]);

        if (!$permit = Permission::find($request->permission))
            $permit = new Permission;

        $permit->permission = $request->permission;
        $permit->comment = $request->comment;
        $permit->save();

        return response()->json([
            'permit' => $permit,
        ]);

    }

}
