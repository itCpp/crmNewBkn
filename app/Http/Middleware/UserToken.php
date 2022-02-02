<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use App\Http\Controllers\Users\Users;

class UserToken
{
    /**
     * Handle an incoming request.
     * @todo Убрать перенаправление на авторизацию по токену староцрэмочного пользователя
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->header('X-Old-Token') and $request->header('X-Old-Token') == $request->bearerToken())
            return (new AuthOldToken)->handle($request, $next);

        if (!$user = Users::checkToken($request->bearerToken())) {

            if ($request->header('X-Automatic-Auth')) {
                if ($token = Users::checkAutomaticAuthToken($request))
                    return Users::automaticUserAuth($request, $token);
            }

            return response()->json(['message' => "Ошибка авторизации"], 401);
        }

        if ($request->header('X-God-Mode'))
            $user = Users::checkGodMode($user, $request->header('X-God-Mode'));

        if ($user->deleted_at)
            return response()->json(['message' => "Доступ закрыт"], 403);

        $request->__user = $user;

        $request->setUserResolver(function () use ($request) {
            return $request->__user;
        });

        return $next($request);
    }
}
