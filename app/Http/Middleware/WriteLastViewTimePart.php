<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Http\Request;

class WriteLastViewTimePart
{
    /**
     * Routes names list
     * 
     * @var array
     */
    protected $routes = [
        'api.agreements' => 'agreements',
        'api.queues.getQueues' => 'queues',
        'api.ratings.callcenter' => 'rating',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $route = $request->route()->getName();
        $name = $this->routes[$route] ?? null;

        Controller::setLastTimeViewPart($name);

        return $response;
    }
}
