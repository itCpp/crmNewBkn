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
            $encrypt = encrypt([
                'request' => $request->all(),
                'response' => $response->getData(true),
            ]);

            Log::channel('webhoock_response')->debug($request->getRequestUri() . " " . $encrypt);
        } finally {
            return $response;
        }
    }
}
