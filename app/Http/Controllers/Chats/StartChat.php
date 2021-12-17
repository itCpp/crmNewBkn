<?php

namespace App\Http\Controllers\Chats;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\ChatRoomsUser;
use App\Models\ChatRoomsViewTime;
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

        $this->chats = $chats;

        if ($get_id)
            return $chats;

        $rooms = $this->getChatsRoomsData($chats);

        usort($rooms, function ($a, $b) {
            return (int) $b['sort'] - (int) $a['sort'];
        });

        return $this->countNewMessages($rooms);
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
        foreach (ChatRoom::whereIn('id', $chats)->get() as $row) {

            // $row->users = $row->users()
            //     ->where('user_id', '!=', $this->request->user()->id)
            //     ->get();

            $users = ChatRoomsUser::where('chat_id', $row->id)
                ->get()
                ->map(function ($row) {
                    return $row->user_id;
                })
                ->toArray();

            $row->users = User::select('id', 'fullName as name', 'pin')
                ->whereIn('id', $users)
                // ->where('id', '!=', $this->request->user()->id)
                ->get();

            // Поиск имени чата при личной беседе
            if (count($row->users) == 2) {
                foreach ($row->users as $user) {
                    if ($this->request->user()->id != $user->id) {
                        $row->name = $user->name ?? null;
                        $row->pin = $user->pin ?? null;
                        $row->user_id = $user->id ?? null;
                        break;
                    }
                }
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
            'sort' => strtotime($message['created_at'] ?? ($row['created_at'] ?? null)),
            'users' => count($row['users'] ?? []) ?: null,
            'members' => $row['users'] ?? [],
        ];
    }

    /**
     * Подсчет количества новых сообщений в чатах
     * 
     * @param array $rooms
     * @return array
     */
    public function countNewMessages($rooms)
    {
        foreach ($rooms as &$room) {
            $room['count'] = $this->getCountNewMessagesChatRoom($room['id'], $this->request->user()->id);
        }

        return $rooms;
    }

    /**
     * Подсчет количетсва новых сообщения дл пользователя
     * 
     * @param int $chat_id
     * @param int $user_id
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
            ->when(!empty($last->last_show), function ($query) use ($last) {
                $query->where('created_at', '>', $last->last_show);
            })
            ->count();
    }

    /**
     * Поиск сотрудника или чат группы
     * 
     * @return array
     */
    public function search()
    {
        return [
            'rooms' => $this->request->search ? $this->searchChatsRooms() : $this->getChatsRooms(),
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
        $user = $this->request->user()->id;

        $exists = ChatRoom::where('user_to_user', 'LIKE', "%{$user}%")
            ->get()
            ->map(function ($row) use ($user) {
                $id = str_replace([",", $user], "", $row->user_to_user);
                return (int) $id;
            })
            ->toArray();

        return User::select('id as user_id', 'fullName as name', 'pin')
            ->whereNotIn('id', [...[$user], ...$exists])
            ->where('state', 'Работает')
            ->where(function ($query) {
                $query->where('pin', 'LIKE', "%{$this->request->search}%")
                    ->orWhere('fullName', 'LIKE', "%{$this->request->search}%");
            })
            ->orderBy('fullName')
            ->limit(20)
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
        $data = $this->countNewMessages(
            $this->getChatsRoomsData([$room->id])
        );

        return $data[0] ?? null;
    }

    /**
     * Применение имени группы для собеседника
     * @todo При переходе на новую авторизацию, заменить ключ с имененм пользователя
     * 
     * @param array $room
     * @param bool $from_crm
     * @return array
     */
    public function setOtherName($room, $from_crm = false)
    {
        // $room['name'] = $this->request->user()->fullName;
        // $room['pin'] = $this->request->user()->pin;
        // $room['user_id'] = $this->request->user()->id;

        // Для определения имени чата во фронтенде
        $room['name'] = null;
        $room['pin'] = null;
        $room['user_id'] = null;

        // Количетсво новых сообщений для каждого пользователя
        if (count($room['members'] ?? []) == 2) {
            foreach ($room['members'] as &$member) {
                $member['count'] = $this->getCountNewMessagesChatRoom($room['id'], $member['id']);

                if ($from_crm and $member['id'] != $this->request->user()->id) {
                    $room['name'] = $this->request->user()->fullName;
                    $room['pin'] = $this->request->user()->pin;
                    $room['user_id'] = $this->request->user()->id;
                }
            }
        }

        return $room;
    }
}
