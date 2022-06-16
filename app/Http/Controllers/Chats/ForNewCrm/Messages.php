<?php

namespace App\Http\Controllers\Chats\ForNewCrm;

use App\Events\Chat\NewMessage;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatRoomsUser;
use Exception;
use Illuminate\Http\Request;
use SplFileInfo;

trait Messages
{
    /**
     * Вывод сообщений
     * 
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function getMessages(Request $request)
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
            ->paginate($this->limit);

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
            'limit' => $this->limit,
        ];
    }

    /**
     * Отправка сообщения
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request)
    {
        return response()->json(
            $this->sendMessageProcess($request)
        );
    }

    /**
     * Обработка запроса отправки сообщения
     * 
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function sendMessageProcess(Request $request)
    {
        $request->chat_id = (int) $request->chat_id;
        $request->toUserId = (int) $request->user_id;

        $message = ChatMessage::create([
            'user_id' => $request->user()->id,
            'chat_id' => (int) $request->chat_id,
            'type' => $request->type,
            'message' => Controller::encrypt($request->message),
            'body' => $this->getBodyMessage($request),
        ]);

        $message->message = $request->message;
        $message = $message->toArray();

        $room_id = $this->createOrRestoreChatRoom($request);
        $room = $this->getChatRoom($room_id, true);

        if ($room) {
            $this->checkOrAttachUsersRoom($room);
            $room_array = $room->toArray();
        }

        broadcast(new NewMessage(
            $message,
            $room_array ?? [],
            $room->users_id ?? []
        )); //->toOthers();

        // if ($message->body)
        //     UploadFilesChatJob::dispatch($message);

        $message['my'] = true;

        return [
            'message' => $message,
            'room' => $room,
        ];
    }

    /**
     * Формирование массива тела сообщения с вложениями
     * 
     * @param  \Illuminate\Http\Request $request
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
     * @param  array
     * @return array
     */
    public function bodyRowTempate($data = [])
    {
        return array_merge($data, [
            'name' => $data['name'] ?? null,
            'hash' => null,
            'type' => null,
            'mimeType' => null,
            'loading' => true,
        ]);
    }
}
