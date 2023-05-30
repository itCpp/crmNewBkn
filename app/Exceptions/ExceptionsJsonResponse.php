<?php

namespace App\Exceptions;

use Exception;

class ExceptionsJsonResponse extends Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        $response['message'] = $this->getMessage();

        if (env('APP_DEBUG', false)) {
            $response = array_merge($response, [
                'request' => $request->all(),
                'trace' => $this->getTrace(),
            ]);
        }

        return response()->json($response, $this->getCode() ?: 400);
    }
}
