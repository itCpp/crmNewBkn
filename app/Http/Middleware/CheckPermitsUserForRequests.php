<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Controllers\Requests\RequestStart;
use Illuminate\Http\Request;

/**
 * Проверка всех разрешений сотрудника, необходимых для вывода заявок
 */
class CheckPermitsUserForRequests
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
        $request->user()->getListPermits(RequestStart::$permitsList);

        return $next($request);
    }
}
