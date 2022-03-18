<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Users\UserData;
use App\Models\User;
use App\Models\UsersSession;
use App\Models\UserWorkTime;
use App\Models\CrmMka\CrmUser;
use App\Models\UserAutomaticAuth;
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
     * @param bool $get_array
     * @return \Illuminate\Http\JsonResponse|array
     */
    public static function adminCheck(Request $request, $get_array = false)
    {
        $response = [
            'permits' => $request->user()->getListPermits([
                'admin_access', # Доступ к админпанели
                'admin_callcenters', # Доступ к настройкам колл-центров
                'admin_callsqueue', # Доступ к настройкам распределения звонков
                'admin_sources', # Доступ к настройкам источников
                'admin_users', # Доступ к сотрудникам
                'block_dev', # Блок разработчика
                'dev_block', # Доступ к блокировкам
                'dev_calls', # Доступ к журналу звонков и настройки их источников
                'dev_offices', # Доступ к настройкам офиса
                'dev_permits', # Создание и изменение прав
                'dev_roles', # Настройка и создание ролей
                'dev_statuses', # Доступ к настройки статусов
                'dev_tabs', # Настройки вкладок
                'god_mode', # Может использовать ЦРМ от другого пользователя
            ]),
        ];

        if ($get_array)
            return $response;

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

    /**
     * Поиск данных сотрудника из старой бд
     * 
     * @param int|string $pin
     * @return
     */
    public static function findUserOldPin($pin)
    {
        if (!$user = CrmUser::wherePin($pin)->first())
            return null;

        return new UderOldCrm((object) $user->toArray());
    }

    /**
     * Проверка автоматической авторизации пользователя
     * 
     * @param \Illuminate\Http\Request $request
     * @return \App\Models\UserAutomaticAuth|null
     */
    public static function checkAutomaticAuthToken(Request $request)
    {
        return UserAutomaticAuth::whereToken($request->header('X-Automatic-Auth'))
            ->whereDate('created_at', now())
            ->first();
    }

    /**
     * Автоматическая авторизация
     * 
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\UserAutomaticAuth $token
     * @return \Illuminate\Http\JsonResponse
     */
    public static function automaticUserAuth(Request $request, UserAutomaticAuth $token)
    {
        if ($token->auth_at)
            return response()->json(['message' => "Токен авторизации недействительный"], 401);

        if ((time() - 60) > strtotime($token->created_at))
            return response()->json(['message' => "Токен авторизации просрочен"], 401);

        if (!$user = User::where('old_pin', $token->pin)->first())
            return response()->json(['message' => "Ошибка авторизации"], 401);

        $token->auth_at = now();
        $token->save();

        $request->__user = new UserData($user);

        $request->setUserResolver(function () use ($request) {
            return $request->__user;
        });

        return Auth::createSession($request);
    }

    /**
     * Поиск идентификатор сотрудника по пину
     * 
     * @param int|string $pin
     * @return null|int
     */
    public static function findUserId($pin)
    {
        if (!$user = User::where('pin', $pin)->first())
            return null;

        return $user->id;
    }
}
