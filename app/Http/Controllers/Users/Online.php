<?php

namespace App\Http\Controllers\Users;

use App\Events\Users\AuthentificationsEvent;
use App\Events\Users\CloseSession;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UsersSession;
use Illuminate\Http\Request;

class Online extends Controller
{
    /**
     * Выводит список активных сессий
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        UsersSession::where('created_at', '>=', date("Y-m-d 00:00:00"))
            ->orderBy('id', "DESC")
            ->get()
            ->each(function ($row) use (&$users) {
                $this->sessions[$row->user_id][] = $row;
                $users[] = $row->user_id;
            });

        $rows = User::whereIn('id', array_unique($users ?? []))
            ->get()
            ->map(function ($row) {

                $row = new UserData($row);

                $row->sessions = $this->sessions[$row->id] ?? [];
                $row->sort = null;

                foreach ($row->sessions as $session) {
                    if (!$row->sort or $row->sort < $session->created_at)
                        $row->sort = $session->created_at;
                }

                return $row;
            })
            ->sortByDesc('sort')
            ->values()
            ->all();

        return response()->json([
            'rows' => $rows,
        ]);
    }

    /**
     * Выводит данные о сессиях пользователя
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        if (!$user = User::find($request->id))
            return response()->json(['message' => "Пользователь не найден"], 400);

        $user = new UserData($user);

        $user->sessions = UsersSession::where('created_at', '>=', date("Y-m-d 00:00:00"))
            ->where('user_id', $user->id)
            ->get();

        return response()->json($user);
    }

    /**
     * Завершает сессию пользователя
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        if (!$row = UsersSession::find($request->id))
            return response()->json(['message' => "Сессия не найдена или уже удалена"], 400);

        $row->delete();

        broadcast(new CloseSession($row->user_id, $row->token));
        broadcast(new AuthentificationsEvent("login", $row->id, $row->user_id))->toOthers();

        return response()->json([
            'message' => "Сессия завершена",
        ]);
    }
}
