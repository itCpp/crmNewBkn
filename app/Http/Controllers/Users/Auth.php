<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\UsersSession;

class Auth extends Controller
{
    
    /**
     * Проверка типа авторизации пользователя
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function loginStart(Request $request) {

        if (!$request->login)
            return response()->json(['message' => "Введите логин или pin"], 400);

        $users = User::where('pin', $request->login)
        ->orWhere('login', $request->login)
        ->get();

        if (!count($users))
            return response()->json(['message' => "Введен неверный логин или pin"], 400);

        if (count($users) > 1)
            return response()->json(['message' => "Найдены задвоенные данные, авторизация невозможна, сообщите об этом руководству"], 400);

        if ($user->deleted_at)
            return response()->json(['message' => "Ваша учетная запись заблокирована"], 400);

        $user = new UserData($users[0]);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name_io,
            'auth_type' => $user->auth_type ?? "secret",
        ]);

    }

    /**
     * Завершение авторизации пользователя
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function login(Request $request) {

        if (!$user = User::find($request->id))
            return response()->json(['message' => "Ошибка авторизации, попробуйте еще раз, обновив страницу"], 400);

        if ($user->deleted_at)
            return response()->json(['message' => "Ваша учетная запись заблокирована"], 400);

        $user->auth_type = $user->auth_type ?? "secret";

        $request->password_user = $user->password;
        $request->__user = new UserData($user);

        if ($user->auth_type == "secret")
            return self::loginFromPassword($request);

        return response()->json(['message' => "Ошибка авторизации, попробуйте еще раз, обновив страницу"], 400);

    }

    /**
     * Авторизация по паролю
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function loginFromPassword(Request $request) {

        $password = self::getHashPass($request->password);

        if (!$request->password_user)
            return response()->json(['message' => "Авторизация по паролю невозможна, так как для Вашего профиля пароль не создан"], 400);

        if ($password != $request->password_user)
            return response()->json(['message' => "Введен неверный пароль"], 400);

        $request->getResponseArray = true;

        $response = Users::check($request);
        $response['token'] = self::createToken($request->__user);

        UsersSession::create([
            'token' => $response['token'],
            'user_id' => $request->__user->id,
            'user_pin' => $request->__user->pin,
            'ip' => $request->ip(),
            'user_agent' => $request->header("User-Agent"),
        ]);

        return response()->json($response);

    }

    /**
     * Создание хэша пароля
     * 
     * @param string $pass
     * @return string
     */
    public static function getHashPass($pass) {

        return md5($pass . env('USER_PASS_SALT'));

    }

    /**
     * Метод создания токена
     * 
     * @param UserData $user Объект данных пользователя
     * @return string
     */
    public static function createToken($user) {

        $salt = "";
        foreach ($user as $key => $value) {
            if (is_string($value))
                $salt .= "{$key}-{$value}-";
        }

        $token = md5($salt . microtime());

        return $token;

    }

    /**
     * Деавторизация пользователя
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function logout(Request $request) {

        return response()->json();

    }

}
