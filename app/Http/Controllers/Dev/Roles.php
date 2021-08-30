<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Role;
use App\Models\Permission;

class Roles extends Controller
{

    /**
     * Загрузка страницы редактирования ролей
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function getAllRoles(Request $request) {

        $roles = Role::orderBy('lvl', "DESC")->get();

        return response()->json([
            'roles' => $roles,
        ]);

    }

    /**
     * Вывод данных одной роли
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function getRole(Request $request) {

        if (!$role = Role::find($request->role))
            return response()->json(['message' => "Роль {$request->role} не найдена"], 400);

        return response()->json([
            'role' => $role,
        ]);

    }

    /**
     * Вывод разрешений роли
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function getPermits(Request $request) {

        if (!$role = Role::find($request->role))
            return response()->json(['message' => "Роль {$request->role} не найдена"], 400);

        foreach ($role->permissions as $permit) {
            $role_permissions[] = $permit->permission;
        }

        return response()->json([
            'role' => $role,
            'permissions' => Permission::all(),
            'role_permissions' => $role_permissions ?? [],
        ]);

    }

    /**
     * Установка права роли
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function setRolePermit(Request $request) {

        if (!$role = Role::find($request->role))
            return response()->json(['message' => "Роль {$request->role} не найдена"], 400);

        $permission = $role->permissions()->where('roles_permissions.permission', $request->permission)->get();
        $permissions = count($permission);
    
        if ($permissions)
            $role->permissions()->detach($request->permission);
        else
            $role->permissions()->attach($request->permission);

        return response()->json([
            'role' => $request->role,
            'permission' => $request->permission,
            'set' => $permissions ? false : true,
        ]);

    }

    /**
     * Сохранение данных роли
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function saveRole(Request $request) {

        $role = Role::withTrashed()->find($request->edit);

        $rules = [
            'role' => "required|regex:/^[a-zA-Z0-9_]+$/i",
        ];

        if (!$role || $role->deleted_at) {
            $rules['role'] .= "|unique:App\Models\Role,role";
        }

        $validate = $request->validate($rules);
        
        if (!$role)
            $role = new Role;

        $role->role = $request->role;
        $role->name = $request->name;
        $role->comment = $request->comment;

        $role->save();

        \App\Models\Log::log($request, $role);

        return response()->json([
            'role' => $role,
        ]);

    }

}
