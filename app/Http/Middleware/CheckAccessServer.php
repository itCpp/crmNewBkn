<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Users\DeveloperBot;
use Closure;
use Illuminate\Http\Request;

class CheckAccessServer
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
        $addrs = explode(",", env("BASE_SERVER_ADDRS", ""));

        if (!in_array($request->ip(), $addrs)) {
            return response()->json([
                'message' => "Доступ ограничен",
            ], 403);
        }

        $request->setUserResolver(function () {
            return (new DeveloperBot)();
        });

        return $next($request);
    }
}
