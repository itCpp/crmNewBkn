<?php

namespace App\Http\Controllers\Chats\ForNewCrm;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatRoomsUser;
use Illuminate\Http\Request;

class Chats extends Controller
{
    use Messages, Rooms;

    /**
     * Количество сообщений на страницу
     * 
     * @var int
     */
    public $limit = 50;

    /**
     * Загрузка данных чата сотрудника
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function start(Request $request)
    {
        return response()->json([
            'rooms' => $this->getChatRooms($request)->sortByDesc('message_at')->values()->all(),
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
        return $this->getMessages($request);
    }

    /**
     * Счетчик сообщений
     * 
     * @param  \Illumiante\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public static function getCounter(Request $request)
    {
        $chats_id = ChatRoomsUser::whereUserId($request->user()->id)
            ->get()
            ->map(function ($row) {
                return $row->chat_id;
            })
            ->toArray();

        $chats = count($chats_id);

        if ($chats) {
            $count = ChatMessage::select('chat_messages.*')
                ->leftJoin('chat_rooms_view_times', function ($join) use ($request) {
                    $join->on('chat_messages.chat_id', '=', 'chat_rooms_view_times.chat_id')
                        ->where('chat_rooms_view_times.user_id', $request->user()->id);
                })
                ->whereColumn('chat_messages.created_at', '>', 'chat_rooms_view_times.last_show')
                ->count();
        }

        return [
            'count' => $count ?? 0,
            'chats' => $chats,
        ];
    }
}
