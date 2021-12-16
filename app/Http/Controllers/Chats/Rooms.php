<?php

namespace App\Http\Controllers\Chats;

use App\Http\Controllers\Controller;
use App\Models\ChatRoomsUser;
use App\Models\ChatRoomsViewTime;
use Illuminate\Http\Request;

class Rooms extends Controller
{
    /**
     * Создание экземпляпа объекта
     * 
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->chat = new StartChat($request);
    }

    /**
     * Вывод данных чат-группы
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRoom(Request $request)
    {
        $users = ChatRoomsUser::where('chat_id', $request->chat_id)
            ->get()
            ->map(function ($row) {
                return $row->user_id;
            })
            ->toArray();

        if (!in_array($request->user()->id, $users))
            return response()->json(['message' => "Доступ к чату ограничен"], 403);

        $last_show = $this->updateLastViewTime($request->chat_id, $request->user()->id);

        $room = $this->getRoomData($request);

        $room['count'] = $this->chat->getCountNewMessagesChatRoom(
            $room['id'] ?? null,
            $request->user()->id
        );

        foreach (ChatRoomsViewTime::where('chat_id', $request->chat_id)->get() as $row) {
            $views[$row->user_id] = $row->last_show;
        }

        foreach ($room['members'] as &$member) {
            $member['last_show'] = $views[$member['id']] ?? null;
        }

        return response()->json([
            'room' => $room,
            'last_show' => $last_show,
            'views' => $views ?? [],
        ]);
    }

    /**
     * Обновление времени просмотра чат команаты
     * 
     * @param int $chat_id
     * @param int $user_id
     * @return string|null Время просмотра до обновления
     */
    public static function updateLastViewTime($chat_id, $user_id)
    {
        $view = ChatRoomsViewTime::firstOrNew([
            'chat_id' => $chat_id,
            'user_id' => $user_id,
        ]);

        $last_show = $view->last_show;

        $view->last_show = now();
        $view->save();

        return $last_show;
    }

    /**
     * Данные чат группы
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function getRoomData(Request $request)
    {
        return $this->chat->getChatsRoomsData([$request->chat_id])[0] ?? [];
    }
}
