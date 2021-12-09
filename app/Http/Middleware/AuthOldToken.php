<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Controllers\Users\UderOldCrm;
use App\Models\CrmMka\CrmUser;
use App\Models\CrmMka\CrmUsersToken;
use Illuminate\Http\Request;

class AuthOldToken
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
        $token = CrmUsersToken::whereToken($request->bearerToken())
            ->whereDate('created_at', now())
            ->orderBy('id', "DESC")
            ->first();

        if (!$token)
            return response()->json(['message' => "Ошибка авторизации"], 401);

        $user = CrmUser::wherePin($token->pin)->whereBaned(0)->whereState('Работает')->first();

        if (!$user)
            return response()->json(['message' => "Доступ ограничен"], 403);

        $request->setUserResolver(function () use ($user) {
            return new UderOldCrm($user->toArray());
        });

        return $next($request);
    }
}
