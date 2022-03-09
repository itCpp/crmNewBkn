<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserCan
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$permits)
    {

        if (!$request->user()->can(...$permits))
            return response()->json(['message' => "Доступ ограничен", 'permits' => $permits], 403);
        
        return $next($request);

    }
}
