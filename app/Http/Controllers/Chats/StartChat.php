<?php

namespace App\Http\Controllers\Chats;

use App\Http\Controllers\Controller;
use App\Models\ChatIdUser;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class StartChat extends Controller
{
    /**
     * Создание экземпляра объекта
     * 
     * @return void
     */
    public function __construct(
        protected Request $request
    ) {
    }

    /**
     * Данные для стартовой страницы чата
     * 
     * @return array
     */
    public function start()
    {
        return [
            'rooms' => $this->getChatsRooms(),
        ];
    }

    /**
     * Вывод списка чатов сотрудника
     * 
     * @return array
     */
    public function getChatsRooms()
    {
        ChatIdUser::where('user_id', $this->request->user()->pin)
        ->get()
        ->map(function ($row) use (&$rooms) {
            $rooms[] = $row->chat_id;

            return $row;
        });

        return $rooms ?? [];
    }
}
