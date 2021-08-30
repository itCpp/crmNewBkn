<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Callcenter;
use App\Models\CallcenterSector;

class AdminUsers extends Controller
{

    /**
     * Вывод списка сотрудников по запросу
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function getUsers(Request $request) {

        $data = User::select(
            'users.*',
            'callcenters.name as callcenter',
            'callcenter_sectors.name as sector'
        )
        ->leftjoin('callcenters', 'callcenters.id', '=', 'users.callcenter_id')
        ->leftjoin('callcenter_sectors', 'callcenter_sectors.id', '=', 'users.callcenter_sector_id');

        if ($request->search) {

        }
        else {
            $data = $data->orderBy('users.id', "DESC")->limit(30);
        }

        foreach ($data->get() as $row)
            $users[] = new UserData($row);

        return response()->json([
            'users' => $users ?? []
        ]);

    }

    /**
     * Вывод данных сотрудника
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function getUser(Request $request) {

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
     * @return response
     */
    public static function getAddUserData(Request $request) {

        $rows = Callcenter::where('active', 1);

        if ($request->__user->callcenter_id)
            $rows = $rows->where('id', $request->__user->callcenter_id);

        foreach ($rows->get() as $row) {

            $sectors = $row->sectors;

            $callcenters[] = $row;

        }

        $user = User::find($request->id);
        $pin = $user->pin ?? self::getNextPinCallcenter($request->__user->callcenter_id);

        return response()->json([
            'callcenter' => $request->__user->callcenter_id, // Колл-центр администратора
            'sector' => $request->__user->callcenter_sector_id, // Сектор администратора
            'callcenters' => $callcenters ?? [],
            'pin' => $pin,
            'user' => $user, // Данные сотрудника для редактирования
        ]);

    }

    /**
     * Поиск следующего pin'a в коллцентре
     * 
     * @param int $id   Идентификтаор коллцентра
     * @return int      Следующий pin коллцентра
     */
    public static function getNextPinCallcenter($id = null) {

        if (!$id)
            return null;

        $pin = $id . "0000";

        // Максимальный pin в диапазоне коллцентра
        $max = User::where([
            ['pin', '>', $pin],
            ['pin', '<', $pin + 10000],
        ])
        ->max('pin');

        $last = User::select('pin')
        ->where([
            ['pin', '>=', $max ?? $pin],
            ['pin', '<', $pin + 10000],
        ])
        ->limit(1)
        ->get();

        if ($last[0]->pin ?? null)
            $pin = $last[0]->pin + 1;

        return (int) $pin;

    }

    /**
     * Данные для смены колл-центра сотрудника
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function getCallCenterData(Request $request) {

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
     * @return response
     */
    public static function saveUser(Request $request) {

        $user = User::find($request->id);

        $rules = [
            'surname' => 'required',
            'name' => 'required',
            'pin' => "required",
        ];

        if (!$user || ($user AND ($user->pin != $request->pin)))
            $rules['pin'] .= "|unique:App\Models\User,pin";

        if ($request->login) {
            if (!$user || ($user AND ($user->login != $request->login)))
                $rules['login'] = "unique:App\Models\User,login";
        }

        $validate = $request->validate($rules);

        if ($request->auth_type == "secret" AND !$request->password AND !$user) {
            return response()->json([
                'message' => "Обязательно укажите пароль при выборе соответствующего способа авторизации",
                'errors' => [
                    'password' => true,
                ],
            ], 400);
        }

        if (!$user)
            $user = new User;

        if (!$user OR ($user AND $request->password))
            $user->password = Auth::getHashPass($request->password);

        $user->pin = $request->pin;
        $user->login = $request->login;
        $user->callcenter_id = $request->callcenter_id;
        $user->callcenter_sector_id = $request->callcenter_sector_id;
        $user->surname = $request->surname;
        $user->name = $request->name;
        $user->patronymic = $request->patronymic;
        $user->telegram_id = $request->telegram_id;
        $user->auth_type = $request->auth_type;

        $user->save();

        \App\Models\Log::log($request, $user);

        $user = new UserData($user);
        
        $user->callcenter = Callcenter::find($user->callcenter_id)->name ?? $user->callcenter_id;
        $user->sector = CallcenterSector::find($user->callcenter_sector_id)->name ?? $user->callcenter_sector_id;

        return response()->json([
            'user' => $user,
        ]);

    }

    /**
     * Блокировка сотрудника
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function blockUser(Request $request) {

        if (!$user = User::find($request->id))
            return response()->json(['message' => "Сотрудник не найден"], 400);

        if ($user->id === $request->__user->id)
            return response()->json(['message' => "Нельзя заблокировать самого себя"], 400);

        $user->deleted_at = $user->deleted_at ? null : date("Y-m-d H:i:s");
        $user->save();

        \App\Models\Log::log($request, $user);

        return response()->json([
            'id' => $user->id,
            'deleted_at' => $user->deleted_at,
        ]);

    }

}
