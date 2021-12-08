<?php

namespace App\Http\Controllers\Chats;

use App\Http\Controllers\Controller;
use App\Models\ChatIdUser;
use App\Models\ChatMessage;
use App\Models\CrmMka\CrmUser as User;
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
        $chats = ChatIdUser::where('user_id', $this->request->user()->pin)
            ->get()
            ->map(function ($row) {
                return $row->chat_id;
            })
            ->toArray();

        $rooms = $this->getChatsRoomsData($chats);

        usort($rooms, function ($a, $b) {
            return (int) $a['sort'] > (int) $b['sort'];
        });

        return $rooms;
    }

    /**
     * Поиск наименований чатов
     * 
     * @param array $chats
     * @return array
     */
    public function getChatsRoomsData($chats = [])
    {
        User::whereIn('id', $chats)
            ->get()
            ->map(function ($row) use (&$rooms) {

                $message = $this->findLastMessage($row->id);

                $rooms[] = [
                    'id' => $row->id,
                    'name' => $row->fullName,
                    'pin' => $row->pin,
                    'message' => $message,
                    'sort' => strtotime($message['created_at'] ?? null),
                ];

                return $row;
            });
        
        return $rooms ?? [];
    }

    /**
     * Поиск последнего сообщения
     * 
     * @param int $chat_id
     * @return null|array
     */
    public function findLastMessage($chat_id)
    {
        if (!$message = ChatMessage::where('chat_id', $chat_id)->orderBy('id', "DESC")->first())
            return null;

        return $message->toArray();
    }
}
