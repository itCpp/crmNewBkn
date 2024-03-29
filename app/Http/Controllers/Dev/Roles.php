<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Users\UserData;
use Illuminate\Http\Request;

use App\Models\Role;
use App\Models\Permission;
use App\Models\RequestsSource;
use App\Models\Tab;
use App\Models\Status;

class Roles extends Controller
{
    /**
     * Загрузка страницы редактирования ролей
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getAllRoles(Request $request)
    {
        $roles = Role::orderBy('lvl', "DESC")->get();

        foreach ($roles as &$role) {
            $role->users_count = $role->users()->count();
        }

        return response()->json([
            'roles' => $roles,
        ]);
    }

    /**
     * Вывод данных одной роли
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getRole(Request $request)
    {
        if (!$role = Role::find($request->role))
            return response()->json(['message' => "Роль {$request->role} не найдена"], 400);

        $role->users_count = $role->users()->count();

        $role->tabs = $role->tabs;
        $role->tabsId = $role->tabs->map(function ($row) {
            return $row->id;
        });

        $role->statusesId = $role->statuses->map(function ($row) {
            return $row->id;
        });

        if ($request->tabsInfo) {
            $response['tabs'] = Tab::orderBy('name')->get();
            $response['statuses'] = Status::orderBy('name')->get();
        }

        $role->is_superadmin = (env("USER_SUPER_ADMIN_ACCESS_FOR_ROLE") and env("USER_SUPER_ADMIN_ROLE") == $role->role);

        if ($request->getPermits) {
            $response['permits'] = $role->permissions;
        }

        if ($request->getUsers) {
            $response['users'] = $role->users->map(function ($row) {
                return new UserData($row);
            })->sortBy('name_full')->values()->all();
        }

        if ($request->getSources) {

            $response['sources'] = RequestsSource::orderBy('name')->get();

            $role->sources = $role->sources()->orderBy('name')->get();
            $role->sourcesId = $role->sources->map(function ($row) {
                return $row->id;
            })->toArray();
        }

        $response['role'] = $role;

        return response()->json($response);
    }

    /**
     * Вывод разрешений роли
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getPermits(Request $request)
    {
        if (!$role = Role::find($request->role))
            return response()->json(['message' => "Роль {$request->role} не найдена"], 400);

        foreach ($role->permissions as $permit) {
            $role_permissions[] = $permit->permission;
        }

        $role->is_superadmin = (env("USER_SUPER_ADMIN_ACCESS_FOR_ROLE") and env("USER_SUPER_ADMIN_ROLE") == $role->role);

        return response()->json([
            'role' => $role,
            'permissions' => Permission::all(),
            'role_permissions' => $role_permissions ?? [],
        ]);
    }

    /**
     * Установка права роли
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function setRolePermit(Request $request)
    {
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
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function saveRole(Request $request)
    {
        $role = Role::withTrashed()->find($request->edit);

        $rules = [
            'role' => "required|regex:/^[a-zA-Z0-9_]+$/i",
        ];

        if (!$role || $role->deleted_at) {
            $rules['role'] .= "|unique:App\Models\Role,role";
        }

        $request->validate($rules);

        if (!$role)
            $role = new Role;

        $role->role = $request->role;
        $role->name = $request->name;
        $role->comment = $request->comment;

        $role->save();

        parent::logData($request, $role);

        $role->users_count = $role->users()->count();

        return response()->json([
            'role' => $role,
        ]);
    }

    /**
     * Присвоение роли доступа к вкладке
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function setTabForRole(Request $request)
    {
        if (!$role = Role::find($request->role))
            return response()->json(['message' => "Роль {$request->role} не найдена"], 400);

        $presence = $role->tabs()->where('id', $request->tabId)->count();

        if (!$presence and $request->checked)
            $role->tabs()->attach($request->tabId);
        else if ($presence and !$request->checked)
            $role->tabs()->detach($request->tabId);

        parent::logData($request, $role);

        $request->tabsInfo = true;

        return self::getRole($request);
    }

    /**
     * Присвоение роли доступа к кстатусу
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function setStatusForRole(Request $request)
    {
        if (!$role = Role::find($request->role))
            return response()->json(['message' => "Роль {$request->role} не найдена"], 400);

        $presence = $role->statuses()->where('id', $request->statusId)->count();

        if (!$presence and $request->checked)
            $role->statuses()->attach($request->statusId);
        else if ($presence and !$request->checked)
            $role->statuses()->detach($request->statusId);

        parent::logData($request, $role);

        $request->tabsInfo = true;

        return self::getRole($request);
    }

    /**
     * Установка роли доступа к источнику
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setSource(Request $request)
    {
        if (!$role = Role::find($request->role))
            return response()->json(['message' => "Роль {$request->role} не найдена"], 400);

        $presence = $role->sources()->where('id', $request->sourceId)->count();

        if (!$presence and $request->checked)
            $role->sources()->attach($request->sourceId);
        else if ($presence and !$request->checked)
            $role->sources()->detach($request->sourceId);

        parent::logData($request, $role);

        $request->getSources = true;

        return self::getRole($request);
    }
}
