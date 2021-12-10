<?php

namespace App\Http\Controllers\Chats;

use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use App\Models\ChatRoomsUser;
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
            'userId' => $this->request->user()->id,
        ];
    }

    /**
     * Вывод списка чатов сотрудника
     * 
     * @param bool $get_id
     * @return array
     */
    public function getChatsRooms($get_id = false)
    {
        $chats = ChatRoomsUser::where('user_id', $this->request->user()->id)
            ->get()
            ->map(function ($row) {
                return $row->chat_id;
            })
            ->toArray();

        if ($get_id)
            return $chats;

        $rooms = $this->getChatsRoomsData($chats);

        usort($rooms, function ($a, $b) {
            return (int) $b['sort'] - (int) $a['sort'];
        });

        return $rooms;
    }

    /**
     * Поиск наименований чатов
     * @todo При переходе на новую ЦРМ заменить выборку отношений пользователей к чат-группам
     * 
     * @param array $chats
     * @return array
     */
    public function getChatsRoomsData($chats = [])
    {
        $rows = ChatRoom::whereIn('id', $chats);

        foreach ($rows->get() as $row) {

            // $row->users = $row->users()
            //     ->where('user_id', '!=', $this->request->user()->id)
            //     ->get();

            $users = ChatRoomsUser::where('chat_id', $row->id)
                ->get()
                ->map(function ($row) {
                    return $row->user_id;
                })
                ->toArray();

            $row->users = User::whereIn('id', $users)
                ->where('id', '!=', $this->request->user()->id)
                ->get();

            if (count($row->users) == 1) {
                $row->name = $row->users[0]->fullName;
                $row->pin = $row->users[0]->pin;
                $row->user_id = $row->users[0]->id;
            }

            $rooms[] = $this->createChatRoomRow($row->toArray());
        }

        return $rooms ?? [];
    }

    /**
     * Создание массива данных чат группы
     * 
     * @param array
     * @return array
     */
    public function createChatRoomRow($row)
    {
        $id = $row['id'] ?? null;
        $message = Messages::findLastMessage($id);

        return [
            'id' => $id,
            'name' => $row['name'] ?? null,
            'pin' => $row['pin'] ?? null,
            'user_id' => $row['user_id'] ?? null,
            'message' => $message,
            'sort' => strtotime($message['created_at'] ?? null),
            'users' => !empty($row['users']) ? count($row['users'] ?? []) + 1 : null,
        ];
    }

    /**
     * Поиск сотрудника или чат группы
     * 
     * @return array
     */
    public function search()
    {
        return [
            'rooms' => $this->searchChatsRooms(),
        ];
    }

    /**
     * Поисковой запрос
     * 
     * @return array
     */
    public function searchChatsRooms()
    {
        return $this->findUsers()
            ->map(function ($row) {
                return array_merge(
                    $this->createChatRoomRow($row->toArray()),
                    ['new_chat_id' => true],
                );
            })
            ->toArray();
    }

    /**
     * Поиск чатов между пользователями по пользователям
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findUsers()
    {
        return User::select('id as user_id', 'fullName as name', 'pin')
            ->where('id', '!=', $this->request->user()->id)
            ->where('state', 'Работает')
            ->where(function ($query) {
                $query->where('pin', 'LIKE', "%{$this->request->search}%")
                    ->orWhere('fullName', 'LIKE', "%{$this->request->search}%");
            })
            ->get();
    }

    /**
     * Данные чат группы для формирования списка
     * 
     * @param \App\Models\ChatRoom $room
     * @return array
     */
    public function getRoomData(ChatRoom $room)
    {
        return $this->getChatsRoomsData([$room->id])[0] ?? null;
    }
}
