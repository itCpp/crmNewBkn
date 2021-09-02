<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Controllers\Users\UserData;

use App\Models\User;
use App\Models\UsersSession;

class Users extends Controller
{

    /**
     * Проверка токена
     * 
     * @param string $token
     * @return bool|object
     */
    public static function checkToken($token) {

        $sessions = UsersSession::where([
            ['token', $token]
        ])
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
    public static function check(Request $request) {

        $response = [
            'user' => $request->__user,
            'permits' => self::getPermitsForMainPage($request),
        ];

        if ($request->getResponseArray)
            return $response;

        return response()->json($response);

    }

    /**
     * Вывод прав пользователя для формирования главной страницы
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public static function getPermitsForMainPage(Request $request) {

        $permits = [
            'admin_access',
        ];

        return $request->__user->getListPermits($permits);

    }

    /**
     * Формирование данных для администратора
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function adminCheck(Request $request) {

        $response = [
            'permits' => $request->__user->getListPermits([
                'block_dev', // Блок разработчика
                'dev_roles', // Настройка и создание ролей
                'dev_permits', // Создание и изменение прав
                'admin_users', // Доступ к сотрудникам
                'admin_callcenters', // Доступ к настройкам колл-центров
                'admin_sources', // Доступ к настройкам источников
            ]),
        ];

        return response()->json($response);

    }

}
