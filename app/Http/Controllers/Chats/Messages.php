<?php

namespace App\Http\Controllers\Chats;

use Exception;
use SplFileInfo;
use App\Events\Chat\NewMessage;
use App\Http\Controllers\Controller;
use App\Jobs\Chat\UploadFilesChatJob;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\ChatRoomsUser;
use App\Models\ChatRoomsViewTime;
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
            ->when($request->point !== null, function ($query) use ($request) {
                $query->where('id', '<=', $request->point);
            })
            ->orderBy('id', "DESC")
            ->paginate(50);

        $end = null;

        foreach ($data as $row) {
            $row->my = $row->user_id == $request->user()->id;
            $row->message = parent::decrypt($row->message);

            $rows[] = $row->toArray();

            if (($request->page == 1 or !$request->page) and !$end) {
                $end = $row->id;
            }
        }

        return [
            'page' => $data->currentPage(),
            'pages' => $data->lastPage(),
            'nextPage' => $data->currentPage() + 1,
            'total' => $data->total(),
            'messages' => $rows ?? [],
            'point' => $end,
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

        if (!$row = ChatMessage::where('chat_id', $chat_id)->orderBy('id', "DESC")->first())
            return null;

        $row->message = parent::decrypt($row->message);

        return $row->toArray();
    }

    /**
     * Отправка сообщений
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function sendMessage(Request $request)
    {
        $request->chat_id = (int) $request->chat_id;

        if (!$request->chat_id and $request->to_user_id)
            $request->chat_id = $this->createOrRestoreChatRoom($request);

        $this->checkOrAttachUsersRoom($request);

        $message = ChatMessage::create([
            'user_id' => $request->user()->id,
            'chat_id' => (int) $request->chat_id,
            'type' => $request->type,
            'message' => parent::encrypt($request->message),
            'body' => $this->getBodyMessage($request),
        ]);

        $message->message = $request->message;
        $data = $message->toArray();

        if ($request->uuid)
            Rooms::updateLastViewTime($message->chat_id, $message->user_id);

        $chat = new StartChat($request);
        $room = $chat->getRoomData($this->room);
        $room = $chat->setOtherName($room, $request->fromCrm !== null);

        broadcast(new NewMessage($data, $room, $this->channels ?? []))
            ->toOthers();

        if ($message->body)
            UploadFilesChatJob::dispatch($message);

        return [
            'message' => array_merge(
                $data,
                ['uuid' => $request->uuid, 'my' => true]
            ),
            'room' => $room,
        ];
    }

    /**
     * Формирование массива тела сообщения с вложениями
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function getBodyMessage(Request $request)
    {
        $body = [];

        if (!$request->urls)
            return $body;

        if (is_array($request->urls)) {
            foreach ($request->urls as $url) {
                $body[] = $this->bodyRowTempate([
                    'name' => (new SplFileInfo($url))->getBasename(),
                    'url' => $url
                ]);
            }
        }

        return $body;
    }

    /**
     * Элемент объекта тела вложений сообщения
     * 
     * @param array
     * @return array
     */
    public function bodyRowTempate($data = [])
    {
        return array_merge(
            $data,
            [
                'name' => $data['name'] ?? null,
                'hash' => null,
                'type' => null,
                'mimeType' => null,
                'loading' => true,
            ]
        );
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
            ->firstOrNew(
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

        //     if ($user != $request->user()->id)
        //         $this->channels[] = $user;

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

            // if ($user != $request->user()->id)
            $this->channels[] = $user;

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
