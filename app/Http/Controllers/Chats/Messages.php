<?php

namespace App\Http\Controllers\Chats;

use Exception;
use App\Http\Controllers\Controller;
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
            ['user_id', $request->user()->pin]
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
}
