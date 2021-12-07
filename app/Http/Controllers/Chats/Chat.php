<?php

namespace App\Http\Controllers\Chats;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class Chat extends Controller
{
    /**
     * Загрузка страницы чата
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function startChat(Request $request)
    {
        return response()->json(
            (new StartChat($request))->start()
        );
    }
}
