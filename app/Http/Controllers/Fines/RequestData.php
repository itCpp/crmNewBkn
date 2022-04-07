<?php

namespace App\Http\Controllers\Fines;

use App\Http\Controllers\Users\UserData;
use App\Models\RequestsRow;
use App\Models\User;
use Illuminate\Http\Request;

class RequestData extends Fines
{
    /**
     * Данные для создания штрафа по заявке
     * 
     * @param  \Illumiante\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request)
    {
        if (!$row = RequestsRow::find($request->input('request')))
            return response()->json(['message' => "Заявка не найдена или уже удалена"], 400);

        if ($request->input('pin') != $row->pin)
            return response()->json(['message' => "Скорее всего в заявке сменился оператор"], 400);

        if (!$user = User::wherePin($row->pin)->first())
            return response()->json(['message' => "Оператор не найден"], 400);

        if ($user->deleted_at)
            return response()->json(['message' => "Сотрудник уволен или просто удален"], 400);

        /** Поиск руководителей сотрудника */
        $chiefs = User::where('deleted_at', null)
            ->where(function ($query) use ($user) {
                $query->when((bool) $user->callcenter_id, function ($query) use ($user) {
                    $query->orWhere(function ($query) use ($user) {
                        $query->whereIn('position_id', $this->envExplode("RATING_CHIEF_POSITION_ID"))
                            ->where('callcenter_id', $user->callcenter_id);
                    });
                })
                    ->when((bool) $user->callcenter_sector_id, function ($query) use ($user) {
                        $query->orWhere(function ($query) use ($user) {
                            $query->whereIn('position_id', $this->envExplode("RATING_ADMIN_POSITION_ID"))
                                ->where('callcenter_id', $user->callcenter_id);
                        });
                    });
            })
            ->orderBy('pin')
            ->get()
            ->map(function ($row) {
                return [
                    'key' => $row->id,
                    'value' => $row->pin,
                    'text' => (new UserData($row))->name_full,
                ];
            });

        $user = new UserData($user);

        return response()->json([
            'request_id' => $row->id,
            'user' => $user,
            'chiefs' => $chiefs ?? [],
            'option' => [
                'key' => $user->id,
                'pin' => $user->pin,
                'title' => $user->name_full,
                'login' => $user->login,
            ],
        ]);
    }

    /**
     * Поиск сотрудников для штрафа
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function find(Request $request)
    {
        $users = User::where('deleted_at', null)
            ->when((bool) $request->search, function ($query) use ($request) {
                $query->where('pin', 'LIKE', "%{$request->search}%")
                    ->orWhere('login', 'LIKE', "%{$request->search}%")
                    ->orWhere(function ($query) use ($request) {
                        $query->where('surname', 'LIKE', "%{$request->search}%")
                            ->orWhere('name', 'LIKE', "%{$request->search}%")
                            ->orWhere('patronymic', 'LIKE', "%{$request->search}%");
                    });
            })
            ->limit(15)
            ->get()
            ->map(function ($row) {
                return new UserData($row);
            });

        $options = $users->map(function ($row) {
            return [
                'key' => $row->id,
                'pin' => $row->pin,
                'title' => $row->name_full,
                'login' => $row->login,
            ];
        });

        return response()->json([
            'users' => $users->toArray(),
            'options' => $options,
        ]);
    }
}
