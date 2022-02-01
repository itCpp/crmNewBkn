<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAuthQuery;
use App\Models\UsersSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

            $query->user = $user;
            $query->date = date("d.m.Y H:i:s", strtotime($query->created_at));

            broadcast(new \App\Events\AuthQuery($query, $user));
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name_io,
            'auth_type' => $auth_type,
            'query_id' => $query->id ?? null,
            'ip' => $request->ip(),
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

        $request->setUserResolver(function () use ($request) {
            return $request->__user;
        });

        if ($user->auth_type == "secret")
            return self::loginFromPassword($request);
        else if ($user->auth_type == "admin")
            return self::loginFromAdmin($request);

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

        return self::createSession($request);
    }

    /**
     * Авторизация через руководителя
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function loginFromAdmin(Request $request)
    {
        if (!$query = UserAuthQuery::find($request->query_id))
            return response()->json(['message' => "Запрос авторизации не найден"], 400);

        if ($request->password != $query->auth_hash)
            return response()->json(['message' => "Ошибка идентификации запроса"], 400);

        $query->auth_hash = null;
        $query->done_at = now();
        $query->save();

        return self::createSession($request);
    }

    /**
     * Создание сессии
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function createSession(Request $request)
    {
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

        return Str::random(60) . $token;
    }

    /**
     * Деавторизация пользователя
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function logout(Request $request)
    {
        $token = $request->bearerToken();

        // Обнуление сессии
        if ($session = UsersSession::where('token', $token)->first())
            $session->delete();

        // Поиск активных сессиий
        $active = UsersSession::where('user_id', $request->user()->id)
            ->whereDate('created_at', now())
            ->count();

        // Запись рабочего времени
        if (!$active)
            $request->user()->writeWorkTime('logout');

        return response()->json([
            'message' => "Goodbye",
        ]);
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

        $user = new UserData(User::find($query->user_id));

        broadcast(new \App\Events\AuthQuery($query, $user));

        return response()->json(['message' => "Запрос отменен"]);
    }

    /**
     * Количество активных запросов авторизации
     * 
     * @param \Illuminate\Http\Request $request
     * @return int
     */
    public static function countAuthQueries($request)
    {
        $permits = $request->user()->getListPermits([
            'user_auth_query_all',
            'user_auth_query_all_sectors'
        ]);

        $query = UserAuthQuery::whereDate('created_at', now());

        if (!$permits->user_auth_query_all and !$permits->user_auth_query_all_sectors)
            $query = $query->where('sector_id', $request->user()->callcenter_sector_id);

        if (!$permits->user_auth_query_all)
            $query = $query->where('callcenter_id', $request->user()->callcenter_id);

        return $query->count();
    }

    /**
     * Вывод списка запросов на авторизацию
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function authQueries(Request $request)
    {
        $data = UserAuthQuery::whereDate('created_at', now());

        $permits = $request->user()->getListPermits([
            'user_auth_query_all',
            'user_auth_query_all_sectors'
        ]);

        if (!$permits->user_auth_query_all and !$permits->user_auth_query_all_sectors)
            $data = $data->where('sector_id', $request->user()->callcenter_sector_id);

        if (!$permits->user_auth_query_all)
            $data = $data->where('callcenter_id', $request->user()->callcenter_id);

        $data = $data->paginate(20);

        foreach ($data as $row) {

            $row->user = new UserData(User::find($row->user_id));
            $row->date = date("d.m.Y H:i:s", strtotime($row->created_at));

            $rows[] = $row->toArray();
        }

        return response()->json([
            'rows' => $rows ?? [],
            'count' => $data->total(),
            'pages' => $data->lastPage(),
            'next' => $data->currentPage() + 1,
        ]);
    }

    /**
     * Завершение запроса авторизации
     * 
     * @param \Illuminate\Http\Request $request
     * @return response
     */
    public static function complete(Request $request)
    {
        $id = $request->done ?: $request->drop;

        if (!$query = UserAuthQuery::find($id))
            return response()->json(['message' => "Запрос авторизации не найден, либо уже был завершен", $id], 400);

        $permits = $request->user()->getListPermits([
            'user_auth_query_all',
            'user_auth_query_all_sectors'
        ]);

        if (!$permits->user_auth_query_all and $query->callcenter_id != $request->user()->callcenter_id)
            return response()->json(['message' => "Доступ к запросу ограничен"], 403);

        if (!$permits->user_auth_query_all_sectors and $query->sector_id != $request->user()->callcenter_sector_id)
            return response()->json(['message' => "Доступ к запросу ограничен"], 403);

        $query->done_pin = $request->user()->pin;

        $data = (object) ['id' => $query->user_id];

        if ($request->drop) {

            $query->done_at = now();

            broadcast(new \App\Events\AuthDone($data));
        }

        if ($request->done) {

            $data->password = md5($query->user_id . microtime());
            $query->auth_hash = $data->password;

            broadcast(new \App\Events\AuthDone($data, true));
        }

        $query->save();

        $user = new UserData(User::find($query->user_id));
        broadcast(new \App\Events\AuthQuery($query, $user))->toOthers();

        return response()->json([
            'message' => "Запрос завершен",
            'id' => $query->id,
        ]);
    }
}
