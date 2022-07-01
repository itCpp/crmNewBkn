<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Jobs\BindUserTelegram;
use App\Models\UserSetting;
use App\Models\UserTelegramIdBind;
use Illuminate\Http\Request;

class Settings extends Controller
{
    /**
     * Установка настройки
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function set(Request $request)
    {
        $row = UserSetting::firstOrCreate(['user_id' => $request->user()->id]);

        $name = $request->input('name');
        $value = $request->input('value');

        if (!isset($row->toArray()[$name]))
            return response()->json(['message' => "Данная настройка не предусмотрена"], 400);

        $row->$name = $value;
        $row->save();

        return response()->json([
            'settings' => $row->toArray(),
            'name' => $name,
            'value' => $value,
        ]);
    }

    /**
     * Формирует код привязки телеграм идентификатора
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function telegramBindStart(Request $request)
    {
        UserTelegramIdBind::whereUserId($request->user()->id)
            ->each(function ($row) {
                $row->delete();
            });

        $row = UserTelegramIdBind::create([
            'user_id' => $request->user()->id,
            'code' => rand(10000, 99999),
        ]);

        return response()->json([
            'code' => $row->code,
        ]);
    }

    /**
     * Обработка команды привязки идентификатора
     * 
     * @param  \Illumiante\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function telegramBind(Request $request)
    {
        $row = UserTelegramIdBind::where([
            ['code', $request->code],
            ['created_at', '>', now()->startOfDay()],
        ])->whereColumn('created_at', 'updated_at')->first();

        if (!$row)
            return response()->json(['message' => "Код подтверждения не найден или уже не действителен"], 400);

        $row->telegram_id = $request->chat_id;
        $row->deleted_at = now();
        $row->save();

        BindUserTelegram::dispatch($row->user_id, $row->telegram_id);

        return response()->json([
            'message' => "Запрос успешно обработан",
        ]);
    }
}
