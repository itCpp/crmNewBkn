<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhoockResponseLogger
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
        $response = $next($request);

        try {
            Log::channel('webhoock_response')->debug($request->getRequestUri(), [
                'request' => $request->all(),
                'response' => $response->getData(true),
            ]);
        } finally {
            return $response;
        }
    }
}
