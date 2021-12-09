<?php

namespace App\Http\Controllers\Chats;

use Exception;
use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use App\Models\ChatRoomsUser;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class Messages extends Controller
{
    /**
     * Вывод сообщений
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function get(Request $request)
    {
        $access = ChatRoomsUser::where([
            ['chat_id', $request->chat_id],
            ['user_id', $request->user()->id]
        ])->count();

        if (!$access and !$request->new_chat_id)
            throw new Exception("Доступ к этому чату ограничен", 403);

        $data = ChatMessage::where('chat_id', $request->chat_id)
            ->orderBy('id', "DESC")
            ->paginate(50);

        foreach ($data as $row) {
            $rows[] = $row->toArray();
        }

        return [
            'pages' => $data->lastPage(),
            'nextPage' => $data->currentPage() + 1,
            'total' => $data->total(),
            'messages' => $rows ?? [],
        ];
    }

    /**
     * Поиск последнего сообщения
     * 
     * @param int $chat_id
     * @return null|array
     */
    public static function findLastMessage($chat_id)
    {
        if (!$chat_id)
            return null;

        if (!$message = ChatMessage::where('chat_id', $chat_id)->orderBy('id', "DESC")->first())
            return null;

        return $message->toArray();
    }

    /**
     * Отправка сообщений
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function sendMessage(Request $request)
    {
        if (!$request->chat_id and $request->to_user_id)
            $request->chat_id = $this->createOrRestoreChatRoom($request);

        $this->checkOrAttachUsersRoom($request);

        $message = ChatMessage::create([
            'user_id' => $request->user()->id,
            'chat_id' => $request->chat_id,
            'message' => $request->message,
            'body' => null,
        ]); 

        return [
            'message' => $message,
        ];
    }

    /**
     * Создание новой чат-группы
     * 
     * @param \Illuminate\Http\Request $request
     * @return int
     */
    public function createOrRestoreChatRoom(Request $request)
    {
        $users = [
            $request->user()->id,
            $request->to_user_id,
        ];

        sort($users);

        $this->room = ChatRoom::withTrashed()
            ->firstOrCreate(
                ['user_to_user' => implode(",", $users)],
                ['user_id' => $request->user()->id]
            );

        if ($this->room->deleted_at)
            $this->room->restore();

        $this->room->save();

        return $this->room->id;
    }

    /**
     * Прикрепление к чат группе собеседников
     * @todo Заменить выборку и определение отношений пользователей к чат-группам
     * 
     * @param \Illuminate\Http\Request $request
     * @return null
     */
    public function checkOrAttachUsersRoom(Request $request)
    {
        $this->room = $this->room ?? null;

        if (!$this->room) {
            $this->room = ChatRoom::find($request->chat_id);
        }

        if (!$this->room->user_to_user)
            return null;

        $users_list = explode(",", $this->room->user_to_user);

        // Заготовка для новой ЦРМ
        // $users = $this->room->users()->get()
        //     ->map(function ($row) {
        //         return $row->id;
        //     })
        //     ->toArray();

        // foreach ($users_list as $user) {

        //     if (in_array($user, $users))
        //         continue;

        //     $this->room->users->attach($user);
        // }

        // Удалить при переходе на новую ЦРМ
        $users = ChatRoomsUser::where('chat_id', $this->room->id)
            ->get()
            ->map(function ($row) {
                return $row->user_id;
            })
            ->toArray();

        foreach ($users_list as $user) {

            if (in_array($user, $users))
                continue;

            ChatRoomsUser::create([
                'user_id' => $user,
                'chat_id' => $this->room->id,
            ]);
        }

        return null;
    }
}
