<?php

namespace App\Http\Controllers\Chats;

use App\Http\Controllers\Users\UserDataFind;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\ChatRoomsUser;
use App\Models\ChatRoomsViewTime;
use Illuminate\Http\Request;

class Chats
{
    use MessagesTrait;

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
     * Загрузка ифнормации о чат группах
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getChatRooms(Request $request)
    {
        return ChatRoomsUser::select('chat_id as id')
            ->where('user_id', $request->user()->id)
            ->get()
            ->map(function ($row) {
                return $this->getChatRoomInfo($row);
            });
    }

    /**
     * Данные одной строки
     * 
     * @param  \App\Models\ChatRoomsUser $row
     * @return \App\Models\ChatRoomsUser
     */
    public function getChatRoomInfo($row)
    {
        $row->users_id = $this->getUsersIdChatRoom($row->id);

        $this->getChatRoomName($row);

        $row->message = Messages::findLastMessage($row->id) ?: [];

        $row->count = $this->getCountNewMessagesChatRoom($row->id, request()->user()->id);

        return $row;
    }

    /**
     * Выводит идентификаторы сотрудников чат группы
     * 
     * @param  int $id
     * @return array
     */
    public function getUsersIdChatRoom($id)
    {
        return ChatRoomsUser::select('user_id')
            ->where('chat_id', $id)
            ->get()
            ->map(function ($row) {
                return $row->user_id;
            })
            ->toArray();
    }

    /**
     * Функция определния имени чат-группы
     * 
     * @param  \App\Models\ChatRoomsUser $row
     * @return \App\Models\ChetRoomsUser
     */
    public function getChatRoomName(ChatRoomsUser &$row)
    {
        if (!is_array($row->users_id))
            $row->users_id = $this->getUsersIdChatRoom($row->id);

        /** Наименование личного чата */
        if (count($row->users_id) == 2) {

            foreach ($row->users_id as $user_id) {

                if (request()->user()->id == $user_id)
                    continue;

                $user = $this->getUserInfo($user_id);

                $row->name = $user->name_full ?? null;
                $row->pin = $user->pin ?? null;
                $row->user_id = $user->id ?? null;

                break;
            }
        }

        return $row;
    }

    /**
     * Поиск данных сотрудника
     * 
     * @param  int $id
     * @return \App\Http\Controllers\Users\UserData
     */
    public function getUserInfo($id)
    {
        if (empty($this->get_user_info))
            $this->get_user_info = [];

        if (!empty($this->get_user_info[$id]))
            return $this->get_user_info[$id];

        return $this->get_user_info[$id] = (new UserDataFind($id))();
    }

    /**
     * Счетчик новых сообщений в чат-группе
     * 
     * @param  int $chat_id
     * @param  int $user_id
     * @return int
     */
    public function getCountNewMessagesChatRoom($chat_id, $user_id)
    {
        $last = ChatRoomsViewTime::where([
            ['chat_id', $chat_id],
            ['user_id', $user_id]
        ])->first();

        return ChatMessage::where([
            ['chat_id', $chat_id],
            ['user_id', '!=', $user_id]
        ])
            ->when((bool) ($last->last_show ?? null), function ($query) use ($last) {
                $query->where('created_at', '>', $last->last_show);
            })
            ->count();
    }

    /**
     * Загрузка данных выбранного чата
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function room(Request $request)
    {
        if (!$room = ChatRoom::find($request->id))
            return response()->json(['message' => "Чат группа не найдена"], 400);

        $chat_room = new ChatRoomsUser();
        $chat_room->id = $room->id;

        $request->chat_id = $room->id;

        return response()->json([
            'room' => $this->getChatRoomInfo($chat_room),
            'messages' => $this->getMessagesChatRoom($request),
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
