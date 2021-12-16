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

        foreach (ChatRoomsViewTime::where('id', $request->chat_id)->get() as $row) {
            $views[$row->user_id] = $row->last_show;
        }

        $view = ChatRoomsViewTime::firstOrNew([
            'id' => $request->chat_id,
            'user_id' => $request->user()->id,
        ]);

        $last_show = $view->last_show;

        $view->last_show = now();
        $view->save();

        return response()->json([
            'room' => $this->getRoomData($request),
            'last_show' => $last_show,
            'views' => $views ?? [],
        ]);
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
