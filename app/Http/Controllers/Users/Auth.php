<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAuthQuery;
use App\Models\UsersSession;
use Illuminate\Http\Request;

class Auth extends Controller
{

    /**
     * Проверка типа авторизации пользователя
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function loginStart(Request $request)
    {

        if (!$request->login)
            return response()->json(['message' => "Введите логин или pin"], 400);

        $users = User::where('pin', $request->login)
            ->orWhere('login', $request->login)
            ->orWhere(function ($query) use ($request) {
                $query->where([
                    ['old_pin', '!=', NULL],
                    ['old_pin', '=', $request->login],
                ]);
            })
            ->get();

        if (!count($users))
            return response()->json(['message' => "Введен неверный логин или pin"], 400);

        if (count($users) > 1)
            return response()->json(['message' => "Найдены задвоенные данные, авторизация невозможна, сообщите об этом руководству"], 400);

        $user = new UserData($users[0]);

        if ($user->deleted_at)
            return response()->json(['message' => "Ваша учетная запись заблокирована"], 400);

        $auth_type = $user->auth_type ?? "secret";

        // Создание запроса на авторизацию
        if ($auth_type == "admin") {
            $query = UserAuthQuery::create([
                'user_id' => $user->id,
                'callcenter_id' => $user->callcenter_id,
                'sector_id' => $user->callcenter_sector_id,
                'ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name_io,
            'auth_type' => $auth_type,
            'query_id' => $query->id ?? null,
        ]);
    }

    /**
     * Завершение авторизации пользователя
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function login(Request $request)
    {

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
    public static function loginFromPassword(Request $request)
    {

        $password = self::getHashPass($request->password);
        $oldpass = "old|" . md5($request->password);

        if (!$request->password_user)
            return response()->json(['message' => "Авторизация по паролю невозможна, так как для Вашего профиля пароль не указан"], 400);

        if ($password != $request->password_user and $oldpass != $request->password_user)
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

        $request->__user->writeWorkTime('login');

        return response()->json($response);
    }

    /**
     * Создание хэша пароля
     * 
     * @param string $pass
     * @return string
     */
    public static function getHashPass($pass)
    {

        return md5($pass . env('USER_PASS_SALT'));
    }

    /**
     * Метод создания токена
     * 
     * @param UserData $user Объект данных пользователя
     * @return string
     */
    public static function createToken($user)
    {

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
    public static function logout(Request $request)
    {

        $request->__user->writeWorkTime('logout');

        return response()->json();
    }

    /**
     * Отмена запроса на авторизацию
     * 
     * @param \Illuminate\Http\Request $request
     * @return reponse
     */
    public static function loginCancel(Request $request)
    {

        $query = UserAuthQuery::where([
            ['ip', $request->ip()],
            ['id', $request->query_id],
            ['user_id', $request->user_id],
        ])->first();

        if (!$query)
            return response()->json(['message' => "Запрос отменить не удалось"], 400);

        $query->delete();

        return response()->json(['message' => "Запрос отменен"]);
    }
}
