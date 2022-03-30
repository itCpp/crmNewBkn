<?php

namespace App\Http\Controllers\Users;

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

                return $row;
            });

        return response()->json([
            'rows' => $rows,
        ]);
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

        broadcast(new CloseSession($row->user_id, $row->token));

        $row->delete();

        return response()->json([
            'message' => "Сессия завершена",
        ]);
    }
}
