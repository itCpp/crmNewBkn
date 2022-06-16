<?php

namespace App\Http\Controllers\Chats\ForNewCrm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Chats extends Controller
{
    use Messages, Rooms;

    /**
     * Количество сообщений на страницу
     * 
     * @var int
     */
    public $limit = 50;

    /**
     * Загрузка данных чата сотрудника
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function start(Request $request)
    {
        return response()->json([
            'rooms' => $this->getChatRooms($request)->sortByDesc('message_at')->values()->all(),
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
        return $this->getMessages($request);
    }
}
