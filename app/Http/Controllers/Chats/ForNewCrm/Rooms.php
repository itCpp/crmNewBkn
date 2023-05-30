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
            return response()->json(['message' => "Чат группа не найдена"], 400);

        $request->chat_id = $room->id;

        $time = ChatRoomsViewTime::firstOrNew([
            'chat_id' => $room->id,
            'user_id' => $request->user()->id,
        ]);

        $time->last_show = now();
        $time->save();

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
        $row = ChatRoom::find($room_id);

        if (!$row->users()->where('user_id', $request->user()->id)->count()) {
            $row->users()->attach($request->user()->id);
        }

        $request->chat_id = $row->id;

        $room = $this->getChatRoomInfo($row);
        $messages = $this->getMessagesChatRoom($request);

        $row->delete();

        return response()->json([
            'room' => $room,
            'messages' => $messages,
            'newChatRoom' => true,
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
        $rows = User::where('deleted_at', null)
            ->where(function ($query) use ($request) {
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
            ->map(function ($row) use ($request) {

                $user = new UserData($row);

                $users = $this->getUserToUserString(
                    $request->user()->id,
                    $user->id,
                );

                return [
                    'id' => md5($users),
                    'message' => null,
                    'name' => $user->name_full,
                    'pin' => $user->pin,
                    'toUserId' => $user->id,
                    'user_id' => $user->id,
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
        $row->searchId = md5($row->user_to_user);

        $row->users_id = $this->getUsersIdChatRoom($row);

        $this->getChatRoomName($row);

        $row->message = Messages::findLastMessage($row->id) ?: [];
        $row->message_at = $row->message['created_at'] ?? $row->updated_at;

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

            $row->user_to_user = collect(explode(",", $row->user_to_user))->map(function ($row) {
                return (int) $row;
            })->toArray();

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
                $row->is_fired = (bool) ($user->deleted_at ?? null);

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
     * @return \App\Models\ChatRoom|null
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

        return $room;
    }

    /**
     * Создание новой или восстановление чат-группы
     * 
     * @param  \Illumiante\Http\Request $request
     * @return int
     */
    public function createOrRestoreChatRoom(Request $request)
    {
        $room = ChatRoom::withTrashed()
            ->firstOrNew(
                ['user_to_user' => $this->getUserToUserString(
                    $request->user()->id,
                    $request->toUserId,
                )],
                ['user_id' => $request->user()->id]
            );

        if ($room->deleted_at)
            $room->restore();

        $room->save();

        return $room->id;
    }

    /**
     * Формирует строку сотрудников
     * 
     * @param  array $users
     * @return string
     */
    public function getUserToUserString(...$users)
    {
        sort($users);

        return implode(",", $users);
    }

    /**
     * Проверяет наличие чат-группы у сотрудника
     * 
     * @param  \App\Models\ChatRoom $room
     * @return array
     * 
     * @todo При проверке группы больше двух сотрудников добавить её определение
     */
    public function checkOrAttachUsersRoom($room)
    {
        foreach ($room->users_id as $user_id) {
            if (!$room->users()->where('user_id', $user_id)->count()) {
                $room->users()->attach($user_id);
                $attach[] = $user_id;
            }
        }

        return $attach ?? [];
    }
}
