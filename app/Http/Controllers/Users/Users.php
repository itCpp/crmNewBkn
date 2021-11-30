<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Users\UserData;
use App\Models\User;
use App\Models\UsersSession;
use App\Models\UserWorkTime;
use Illuminate\Http\Request;

class Users extends Controller
{
    /**
     * Проверка токена
     * 
     * @param string $token
     * @return bool|object
     */
    public static function checkToken($token)
    {
        $sessions = UsersSession::where([
            ['token', $token]
        ])
            ->whereDate('created_at', now())
            ->get();

        if (count($sessions) != 1)
            return false;

        $session = $sessions[0] ?? null;

        if (!$session)
            return false;

        $session->active_at = date("Y-m-d H:i:s");
        $session->save();

        if (!$user = User::find($session->user_id))
            return false;

        return new UserData($user);
    }

    /**
     * Первоначальная загрузка страницы со всеми данными
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function check(Request $request)
    {
        $worktime = UserWorkTime::where('user_pin', $request->user()->pin)
            ->orderBy('id', 'DESC')
            ->first();

        $permits = self::getPermitsForMainPage($request);

        // Количество запросов на авторизацию
        $authQueries = $permits->user_auth_query ? Auth::countAuthQueries($request) : 0;

        $response = [
            'user' => $request->user(),
            'permits' => $permits,
            'worktime' => $worktime ? Worktime::getDataForEvent($worktime) : [],
            'authQueries' => $authQueries,
        ];

        if ($request->getResponseArray)
            return $response;

        return response()->json($response);
    }

    /**
     * Вывод прав пользователя для формирования главной страницы
     * 
     * @param \Illuminate\Http\Request $request
     * @return Permissions
     */
    public static function getPermitsForMainPage(Request $request)
    {
        $permits = [
            'admin_access', # Доступ к админ-панели
            'user_auth_query', # Может обработать запрос авторизации пользователя
        ];

        return $request->user()->getListPermits($permits);
    }

    /**
     * Формирование данных для администратора
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function adminCheck(Request $request)
    {
        $response = [
            'permits' => $request->user()->getListPermits([
                'block_dev', # Блок разработчика
                'dev_roles', # Настройка и создание ролей
                'dev_permits', # Создание и изменение прав
                'admin_users', # Доступ к сотрудникам
                'admin_callcenters', # Доступ к настройкам колл-центров
                'admin_sources', # Доступ к настройкам источников
                'dev_statuses', # Доступ к настройки статусов
                'dev_tabs', # Настройки вкладок
                'god_mode', # Может использовать ЦРМ от другого пользователя
                'dev_calls', # Доступ к журналу звонков и настройки их источников
                'admin_callsqueue', # Доступ к настройкам распределения звонков
                'dev_offices', # Доступ к настройкам офиса
                'dev_block', # Доступ к блокировкам
            ]),
        ];

        return response()->json($response);
    }

    /**
     * Подмена пользователя для имитации ЦРМ
     * 
     * @param UserData $user
     * @param int $id Идентификатор пользователя
     * @return UserData
     */
    public static function checkGodMode(UserData $user, $id)
    {
        if (!$user->can('god_mode'))
            return $user;

        if ($subject = User::find($id))
            return new UserData($subject);

        return $user;
    }

    /**
     * Поиск сотркдника по его персональному идентификационному номеру
     * 
     * @param int $pin
     * @return UserData|null
     */
    public static function findUserPin($pin)
    {
        if (!$user = User::where('pin', $pin)->first())
            return null;

        return new UserData($user);
    }
}
