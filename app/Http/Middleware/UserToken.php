<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use App\Http\Controllers\Users\Users;

class UserToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        if (!$user = Users::checkToken($request->header('Authorization')))
            return response()->json(['message' => "Ошибка авторизации"], 401);

        if ($request->header('X-God-Mode'))
            $user = Users::checkGodMode($user, $request->header('X-God-Mode'));

        if ($user->deleted_at)
            return response()->json(['message' => "Доступ закрыт"], 403);

        $request->__user = $user;

        return $next($request);

    }
}
