<?php

namespace App\Exceptions;

use Exception;

class CreateRequestsSqlQuery extends Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage(),
            'request' => $request->all(),
            'trace' => $this->getTrace(),
        ], 400);
    }
}
