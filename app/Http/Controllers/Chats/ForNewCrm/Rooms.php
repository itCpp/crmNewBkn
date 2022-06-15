<?php

namespace App\Http\Controllers\Chats\ForNewCrm;

use App\Http\Controllers\Chats\Messages;
use App\Http\Controllers\Users\UserData;
use App\Http\Controllers\Users\UserDataFind;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\ChatRoomsUser;
use App\Models\ChatRoomsViewTime;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait Rooms
{
    /**
     * Загрузка ифнормации о чат группах
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getChatRooms(Request $request)
    {
        $chats_id = ChatRoomsUser::where('user_id', $request->user()->id)
            ->get()
            ->map(function ($row) {
                return $row->chat_id;
            })
            ->toArray();

        return ChatRoom::whereIn('id', $chats_id)
            ->get()
            ->map(function ($row) {
                return $this->getChatRoomInfo($row);
            });
    }

    /**
     * Загрузка данных выбранного чата
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function room(Request $request)
    {
        $id = is_integer($request->id) ? $request->id : null;

        $room = ChatRoom::find($id);

        if (!$room and $request->toSearch and $request->toUserId)
            return $this->roomForNewChat($request);

        if (!$room)
            return response()->json(['message' => "Чат группа не найдена", $id], 400);

        $request->chat_id = $room->id;

        return response()->json([
            'room' => $this->getChatRoomInfo($room),
            'messages' => $this->getMessagesChatRoom($request),
        ]);
    }

    /**
     * Выводит данные для новой чат-группы
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function roomForNewChat(Request $request)
    {
        $room_id = $this->createOrRestoreChatRoom($request);
        $room = ChatRoom::find($room_id);

        if (!$room->users()->where('user_id', $request->user()->id)->count()) {
            $room->users()->attach($request->user()->id);
        }

        $request->chat_id = $room->id;

        return response()->json([
            'room' => $this->getChatRoomInfo($room),
            'messages' => $this->getMessagesChatRoom($request),
            'newChatRoom' => true,
            $request->user()->id
        ]);
    }

    /**
     * Поиск чат группы
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $rows = User::where(function ($query) use ($request) {
            $query->where('surname', 'LIKE', "%{$request->search}%")
                ->orWhere('name', 'LIKE', "%{$request->search}%")
                ->orWhere('patronymic', 'LIKE', "%{$request->search}%")
                ->orWhere('login', 'LIKE', "%{$request->search}%")
                ->orWhere('pin', 'LIKE', "%{$request->search}%");
        })
            ->orderBy('surname')
            ->orderBy('name')
            ->orderBy('patronymic')
            ->limit(25)
            ->get()
            ->map(function ($row) {

                $user = new UserData($row);

                return [
                    'id' => Str::uuid(),
                    'message' => null,
                    'name' => $user->name_full,
                    'pin' => $user->pin,
                    'toUserId' => $user->id,
                ];
            });

        return response()->json([
            'rows' => $rows ?? [],
        ]);
    }

    /**
     * Данные одной строки
     * 
     * @param  \App\Models\ChatRoom $row
     * @return \App\Models\ChatRoom
     */
    public function getChatRoomInfo($row)
    {
        $row->users_id = $this->getUsersIdChatRoom($row);

        $this->getChatRoomName($row);

        $row->message = Messages::findLastMessage($row->id) ?: [];

        $row->count = $this->getCountNewMessagesChatRoom($row->id, request()->user()->id);

        return $row;
    }

    /**
     * Выводит идентификаторы сотрудников чат группы
     * 
     * @param  \App\Models\ChatRoom $row
     * @return array
     */
    public function getUsersIdChatRoom(ChatRoom $row)
    {
        $users_id = ChatRoomsUser::select('user_id')
            ->where('chat_id', $row->id)
            ->get()
            ->map(function ($row) {
                return $row->user_id;
            })
            ->toArray();

        /** Перепрвоерка удаленных */
        if ($row->user_to_user) {

            $row->user_to_user = explode(",", $row->user_to_user);

            if ($diff = array_diff($row->user_to_user, $users_id))
                $users_id = [...$users_id, ...$diff];
        }

        return $users_id;
    }

    /**
     * Функция определния имени чат-группы
     * 
     * @param  \App\Models\ChatRoom $row
     * @return \App\Models\ChatRoom
     */
    public function getChatRoomName(ChatRoom &$row)
    {
        if (!is_array($row->users_id))
            $row->users_id = $this->getUsersIdChatRoom($row);

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
     * Выводит данные одной чат группы
     * 
     * @param  int $id
     * @param  bool $find_users
     * @return array|null
     */
    public function getChatRoom($id, $find_users = false)
    {
        if (!$room = ChatRoom::find($id))
            return null;

        $room = $this->getChatRoomInfo($room);

        if ($find_users) {
            $room->users = User::whereIn('id', $room->users_id)
                ->get()
                ->map(function ($row) {
                    return new UserData($row);
                });
        }

        return $room->toArray();
    }

    /**
     * Создание новой или восстановление чат-группы
     * 
     * @param  \Illumiante\Http\Request $request
     * @return int
     */
    public function createOrRestoreChatRoom(Request $request)
    {
        $users = [
            $request->user()->id,
            $request->toUserId,
        ];

        sort($users);

        $room = ChatRoom::withTrashed()
            ->firstOrNew(
                ['user_to_user' => implode(",", $users)],
                ['user_id' => $request->user()->id]
            );

        if ($room->deleted_at)
            $room->restore();

        $room->save();

        return $room->id;
    }
}
