<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ExportTokenMiddleware
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
        if ($request->token != env("EXPORT_TOKEN")) {
            abort(401, "Недействительный токен");
        }

        return $next($request);
    }
}
