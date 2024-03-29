<?php

namespace App\Http\Controllers\Users;

use App\Events\Users\NotificationsEvent;
use App\Http\Controllers\Controller;
use App\Models\Base\CrmImagePersonal;
use App\Models\Base\Personal;
use App\Models\Callcenter;
use App\Models\CallcenterSector;
use App\Models\Notification;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Saratov\CrmImagePersonal as SaratovCrmImagePersonal;
use App\Models\Saratov\Personal as SaratovPersonal;
use App\Models\User;
use App\Models\UsersPosition;
use App\Models\UsersPositionsStory;
use Illuminate\Http\Request;

class AdminUsers extends Controller
{
    /**
     * Вывод списка сотрудников по запросу
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getUsers(Request $request)
    {
        $users = User::select(
            'users.*',
            'callcenters.name as callcenter',
            'callcenter_sectors.name as sector'
        )
            ->leftjoin('callcenters', 'callcenters.id', '=', 'users.callcenter_id')
            ->leftjoin('callcenter_sectors', 'callcenter_sectors.id', '=', 'users.callcenter_sector_id')
            ->when((bool) $request->search, function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $query->where('users.surname', 'LIKE', "%{$request->search}%")
                        ->orWhere('users.name', 'LIKE', "%{$request->search}%")
                        ->orWhere('users.patronymic', 'LIKE', "%{$request->search}%")
                        ->orWhereRaw("concat(`users`.`surname`, ' ', `users`.`name`, ' ', `users`.`patronymic`) LIKE '%{$request->search}%'")
                        ->orWhere('users.pin', 'LIKE', "%{$request->search}%")
                        ->orWhere('users.old_pin', 'LIKE', "%{$request->search}%")
                        ->orWhere('users.login', 'LIKE', "%{$request->search}%");
                })
                    ->orderBy('users.deleted_at')
                    ->orderBy('users.surname')
                    ->orderBy('users.name')
                    ->orderBy('users.patronymic');
            })
            ->when(!(bool) $request->search, function ($query) {
                $query->where('deleted_at', null)
                    ->orderBy('users.created_at', "DESC");
            })
            ->limit(33)
            ->get()
            ->map(function ($row) {
                return new UserData($row);
            });

        return response()->json([
            'users' => $users ?? []
        ]);
    }

    /**
     * Вывод данных сотрудника
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getUser(Request $request)
    {
        if (!$user = User::find($request->id))
            return response()->json(['message' => "Данные сотрудника не найдены"], 400);

        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * Данные для вывода окна создания сотрудника
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getAddUserData(Request $request)
    {
        $rows = Callcenter::where('active', 1);

        if ($request->user()->callcenter_id)
            $rows = $rows->where('id', $request->user()->callcenter_id);

        foreach ($rows->get() as $row) {

            $sectors = $row->sectors;

            $callcenters[] = $row;
        }

        $user = User::find(is_array($request->id) ? null : $request->id);

        if ($request->id and $user) {
            $pin = $user->pin ?? self::getNextPinCallcenter($request->user()->callcenter_id);
        } else {
            $pin = self::getNextPinCallcenter($request->user()->callcenter_id);
        }

        /** Информация о сотруднике в БАЗАх */
        if ($user) {

            if ($pers = SaratovPersonal::wherePin($user->pin)->first()) {

                $img = SaratovCrmImagePersonal::where('synhId', $pers->id)
                    ->orderBy('id', "DESC")
                    ->first();

                if ($img) {
                    $photo = env("BASE_SARATOV_URL") . "/modules/personal/php/upload/image/" . $img->path;
                }
            } else if ($pers = Personal::wherePin($user->pin)->first()) {

                $img = CrmImagePersonal::where('synhId', $pers->id)
                    ->orderBy('id', "DESC")
                    ->first();

                if ($img) {
                    $photo = env("BASE_URL") . "/modules/personal/php/upload/image/" . $img->path;
                }
            }
        }

        return response()->json([
            'callcenter' => $request->user()->callcenter_id, // Колл-центр администратора
            'sector' => $request->user()->callcenter_sector_id, // Сектор администратора
            'callcenters' => $callcenters ?? [],
            'pin' => $pin,
            'user' => $user, // Данные сотрудника для редактирования
            'photo' => $photo ?? null,
            'personal' => (bool) ($pers ?? null),
            'positions' => UsersPosition::all(),
            'auth_types' => [
                ['text' => "По паролю", 'value' => "secret"],
                ['text' => "Через руководителя", 'value' => "admin"],
            ],
        ]);
    }

    /**
     * Поиск следующего pin'a в коллцентре
     * 
     * @param int $id Идентификатор коллцентра
     * @return int Следующий pin коллцентра
     */
    public static function getNextPinCallcenter($id = null)
    {
        if (!$id)
            return null;

        $pin = (int) $id . "0000";

        /** Максимальный pin в диапазоне коллцентра */
        $max = User::where([
            ['pin', '>=', $pin],
            ['pin', '<', $pin + 10000],
        ])->max('pin');

        /** Первый существующий пин после максимального */
        $last = User::select('pin')->where([
            ['pin', '>=', $max ?: $pin],
            ['pin', '<', $pin + 10000],
        ])->first();

        if ($last->pin ?? null)
            $pin = $last->pin + 1;

        if (User::wherePin($pin)->count())
            $pin = User::max('pin') + 1;

        return (int) $pin;
    }

    /**
     * Данные для смены колл-центра сотрудника
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getCallCenterData(Request $request)
    {
        if ($request->id !== null) {
            if (!$row = Callcenter::find($request->id))
                return response()->json(['message' => "Колл-центр не найден"], 400);
        }

        return response()->json([
            'pin' => self::getNextPinCallcenter($row->id ?? null),
            'sectors' => $row->sectors ?? [],
        ]);
    }

    /**
     * Создание или обновление данных сотрудника
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function saveUser(Request $request)
    {
        $user = User::find($request->id);

        $rules = [
            'surname' => 'required',
            'name' => 'required',
            'pin' => "required",
            'login' => "required",
        ];

        if (!$user || ($user and ($user->pin != $request->pin)))
            $rules['pin'] .= "|unique:App\Models\User,pin";

        if ($request->login) {
            if (!$user || ($user and ($user->login != $request->login)))
                $rules['login'] = "required|unique:App\Models\User,login";
        }

        $request->validate($rules, [
            'login.required' => "Номер телефона не указан или указан неверно.",
            'login.unique' => "Этот номер телефона уже используется у другого сотрудника.",
        ]);

        if ($request->auth_type == "secret" and !$request->password and !$user) {
            return response()->json([
                'message' => "Обязательно укажите пароль при выборе соответствующего способа авторизации",
                'errors' => [
                    'password' => true,
                ],
            ], 400);
        }

        if (!$user)
            $user = new User;

        if (!$user or ($user and $request->password))
            $user->password = Auth::getHashPass($request->password);

        if ($user->pin and $user->pin != $request->pin)
            $changed_pin = $user->pin;

        $user->pin = $request->pin;
        $user->login = $request->login;
        $user->callcenter_id = $request->callcenter_id;
        $user->callcenter_sector_id = $request->callcenter_sector_id;
        $user->surname = $request->surname;
        $user->name = $request->name;
        $user->patronymic = $request->patronymic;
        $user->telegram_id = $request->telegram_id;
        $user->auth_type = $request->auth_type;

        $old_position_id = $user->position_id;
        $user->position_id = $request->position_id;

        $user->save();

        if ($request->create_caller) {

            if (!$user->roles()->where('users_roles.role', "caller")->count())
                $user->roles()->attach("caller");
        }

        $log = parent::logData($request, $user, $user->password ? true : false);

        // Логирование изменения должности
        if ($old_position_id != $request->position_id) {
            UsersPositionsStory::create([
                'log_id' => $log->id,
                'user_id' => $user->id,
                'position_old' => $old_position_id,
                'position_new' => $user->position_id,
                'created_at' => now(),
            ]);
        }

        $user = new UserData($user);

        if ($request->create_personal_base) {

            if ($changed_pin ?? null)
                $user->changed_pin = $changed_pin;

            self::createPersonalBase($user);
        }

        $user->callcenter = Callcenter::find($user->callcenter_id)->name ?? $user->callcenter_id;
        $user->sector = CallcenterSector::find($user->callcenter_sector_id)->name ?? $user->callcenter_sector_id;

        if ($request->create_notification) {

            broadcast(new NotificationsEvent(
                Notification::create([
                    'user' => $request->user()->pin,
                    'notif_type' => "create_user",
                    'notification' => "Вы создали учетную запись для нового сотрудника",
                    'data' => $user,
                ]),
                $request->user()->id,
            ));
        }

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Блокировка сотрудника
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function blockUser(Request $request)
    {
        if (!$user = User::find($request->id))
            return response()->json(['message' => "Сотрудник не найден"], 400);

        if ($user->id === $request->__user->id)
            return response()->json(['message' => "Нельзя заблокировать самого себя"], 400);

        $user->deleted_at = $user->deleted_at ? null : date("Y-m-d H:i:s");
        $user->save();

        parent::logData($request, $user);

        return response()->json([
            'id' => $user->id,
            'deleted_at' => $user->deleted_at,
        ]);
    }

    /**
     * Вывод ролей и разрешений для сотрудника
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getRolesAndPermits(Request $request)
    {
        if (!$user = User::find($request->id))
            return response()->json(['message' => "Пользователь не найден"], 400);

        // Все роли
        $roles = new Role;

        // Роли с учетом своих ролей
        if ($request->user()->roles and !$request->user()->superadmin) {
            $roles = $roles->whereIn('role', $request->user()->roles);
        }

        $roles = $roles->orderBy('lvl', "DESC")->get();

        $permits_all = []; // Все права
        $permits_list = []; // Список всех прав

        foreach (Permission::all() as $permit) {
            $permits_all[] = $permit;
            $permits_list[] = $permit->permission;
        }

        // Разрешенные права
        $rights = $request->user()->getListPermits($permits_list);

        // Список разрешений, с учетом своих разрешений
        foreach ($permits_all as $permit) {
            if ($rights->{$permit->permission})
                $permits[] = $permit;
        }

        // Роли сотрудника
        foreach ($user->roles as $role) {

            $role->permissions()
                ->when(count($roles_permits ?? []) > 0, function ($query) use (&$roles_permits) {
                    $query->whereNotIn('permissions.permission', $roles_permits);
                })
                ->get()
                ->each(function ($row) use (&$roles_permits) {
                    $roles_permits[] = $row->permission;
                });

            $user_roles[] = $role->role;
        }

        // Разрешения сотрудника
        foreach ($user->permissions as $permission) {
            $user_permits[] = $permission->permission;
        }

        return response()->json([
            'roles' => $roles ?? [],
            'permits' => $permits ?? [],
            'user_roles' => $user_roles ?? [],
            'user_permits' => $user_permits ?? [],
            'roles_permits' => self::getPermitsFromRoles($user),
            'superadmin' => (new UserData($user))->superadmin,
        ]);
    }

    /**
     * Установка роли пользователю
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function setUserRole(Request $request)
    {
        if (!$user = User::find($request->id))
            return response()->json(['message' => "Пользователь не найден"], 400);

        if (!$role = Role::find($request->role))
            return response()->json(['message' => "Роль с указанным идентификатором не найдена"], 400);

        if ($request->__user->getRoleLevel() < $role->lvl)
            return response()->json(['message' => "Недостаточно прав для настройки этой роли"], 403);

        $role = $user->roles()->where('users_roles.role', $request->role)->get();
        $search = count($role);

        if ($search)
            $user->roles()->detach($request->role);
        else
            $user->roles()->attach($request->role);

        foreach ($user->roles as $role)
            $roles[] = $role->role;

        return response()->json([
            'role' => $request->role,
            'roles' => $roles ?? [],
            'checked' => $search ? false : true,
            'roles_permits' => self::getPermitsFromRoles($user),
            'superadmin' => (new UserData($user))->superadmin,
        ]);
    }

    /**
     * Выводит список разрешений, принадлежащих ролям пользователя
     * 
     * @param  \App\Models\User $user
     * @return array
     */
    public static function getPermitsFromRoles($user)
    {
        foreach ($user->roles as $role) {

            $role->permissions()
                ->when(count($roles_permits ?? []) > 0, function ($query) use (&$roles_permits) {
                    $query->whereNotIn('permissions.permission', $roles_permits);
                })
                ->get()
                ->each(function ($row) use (&$roles_permits) {
                    $roles_permits[] = $row->permission;
                });
        }

        return array_values(array_unique($roles_permits ?? []));
    }

    /**
     * Установка разрешения пользователю
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function setUserPermission(Request $request)
    {
        if (!$user = User::find($request->id))
            return response()->json(['message' => "Пользователь не найден"], 400);

        if (!$permission = Permission::find($request->permission))
            return response()->json(['message' => "Разрешение с указанным идентификатором не найдено"], 400);

        if (!$request->__user->can($request->permission))
            return response()->json(['message' => "Вы не можете сменить разрешение не имея на него права"], 403);

        $permission = $user->permissions()->where('permission_id', $request->permission)->get();
        $permissions = count($permission);

        if ($permissions)
            $user->permissions()->detach($request->permission);
        else
            $user->permissions()->attach($request->permission);

        return response()->json([
            'permission' => $request->permission,
            'checked' =>  $permissions ? false : true,
        ]);
    }

    /**
     * Создание учетной записи нового сотрудника
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function create(Request $request)
    {
        $callcenter_id = $request->user()->callcenter_id ?: 2;

        $pin = $callcenter_id
            ? self::getNextPinCallcenter($callcenter_id)
            : (User::max('pin') + 1);

        $request->merge([
            'auth_type' => "admin",
            'login' => parent::checkPhone($request->login, 3) ?: null,
            'pin' => $pin,
            'callcenter_id' => $callcenter_id,
            'callcenter_sector_id' => $request->user()->callcenter_sector_id ?: 2,
            'create_notification' => true,
            'create_caller' => true,
            'create_personal_base' => true,
        ]);

        return self::saveUser($request);
    }

    /**
     * Создание сотрудника в базе
     * 
     * @param  \App\Http\Controllers\Users\UserData $user
     * @return \App\Models\Saratov\Personal
     */
    public static function createPersonalBase($user)
    {
        if ($row = SaratovPersonal::wherePin($user->changed_pin ?: $user->pin)->first()) {

            $row->pin = $user->changed_pin ?: $user->pin;
            $row->fio = $user->name_full ?: $user->pin;

            $row->save();

            return $row;
        }

        return SaratovPersonal::create([
            'pin' => $user->pin,
            'doljnost' => "Оператор",
            'fio' => $user->name_full,
            'telefonl' => $user->login,
            'otdel' => "Колл-центр",
            'state' => "Работает",
            'workStart' => date("Y-m-d"),
        ]);
    }
}
