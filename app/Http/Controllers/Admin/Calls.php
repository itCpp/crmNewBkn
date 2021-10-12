<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IncomingCall;
use App\Models\IncomingCallsToSource;
use Illuminate\Http\Request;

class Calls extends Controller
{
    
    /**
     * Загрузка страницы журнала звонков
     * 
     * @param Illuminate\Http\Request $request
     * @return response
     */
    public static function start(Request $request)
    {

        $data = IncomingCall::orderBy('id', "DESC")
            ->limit(40)
            ->get();

        foreach ($data as $call) {
            $call->phone = parent::decrypt($call->phone);
            $calls[] = $call->toArray();
        }

        return response()->json([
            'calls' => $calls ?? [],
        ]);
    }

}
