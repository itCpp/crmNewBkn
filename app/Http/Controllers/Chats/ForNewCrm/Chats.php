<?php

namespace App\Http\Controllers\Chats\ForNewCrm;

use App\Http\Controllers\Chats\Messages;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Chats extends Controller
{
    use MessagesTrait, Rooms;

    /**
     * Загрузка данных чата сотрудника
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function start(Request $request)
    {
        return response()->json([
            'rooms' => $this->getChatRooms($request),
        ]);
    }

    /**
     * Вывод сообщений
     * 
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function getMessagesChatRoom(Request $request)
    {
        return Messages::get($request);
    }
}
