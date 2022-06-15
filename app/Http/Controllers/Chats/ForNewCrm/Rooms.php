<?php

namespace App\Http\Controllers\Chats\ForNewCrm;

use App\Http\Controllers\Users\UserData;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait Rooms
{
    /**
     * Поиск чат группы
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $rows = User::where(function ($query) use ($request) {
            $query->where('surname', 'LIKE', "%{$request->search}%")
                ->orWhere('name', 'LIKE', "%{$request->search}%")
                ->orWhere('patronymic', 'LIKE', "%{$request->search}%")
                ->orWhere('login', 'LIKE', "%{$request->search}%")
                ->orWhere('pin', 'LIKE', "%{$request->search}%");
        })
            ->orderBy('surname')
            ->orderBy('name')
            ->orderBy('patronymic')
            ->limit(25)
            ->get()
            ->map(function ($row) {

                $user = new UserData($row);

                return [
                    'id' => Str::uuid(),
                    'message' => null,
                    'name' => $user->name_full,
                    'pin' => $user->pin,
                    'to_user_id' => $user->id,
                ];
            });

        return response()->json([
            'rows' => $rows ?? [],
        ]);
    }
}