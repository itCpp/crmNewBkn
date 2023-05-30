<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Requests\Events as RequestsEvents;
use App\Models\Incomings\IncomingEvent;
use Illuminate\Http\Request;

class Events extends Controller
{
    /**
     * Вывод события
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        return (new RequestsEvents)->get($request);
    }

    /**
     * Вывод типо событий
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function types()
    {
        return IncomingEvent::select('api_type')->distinct()->get()->map(function ($row) {
            return $row->api_type;
        });
    }
}
