<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Requests\Counters;
use App\Jobs\BindUserTelegram;
use App\Models\UserSetting;
use App\Models\UserTelegramIdBind;
use Illuminate\Http\Request;

class Settings extends Controller
{
    /**
     * Стандартный массив настроек
     * 
     * @var array
     */
    const DEFAULT = [
        'short_menu' => false,
        'counter_widjet_records' => false,
        'counter_widjet_comings' => false,
        'counter_widjet_drain' => false,
    ];

    /**
     * Типы ключей для асчета счетчика
     * 
     * @var array
     */
    protected $counter_widjets = [
        'counter_widjet_records',
        'counter_widjet_comings',
        'counter_widjet_drain',
    ];

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

        $response = [
            'settings' => $row->toArray(),
            'name' => $name,
            'value' => $value,
        ];

        if (in_array($name, $this->counter_widjets))
            $response['counter'] = Counters::getCounterWidjets();

        return response()->json($response);
    }

    /**
     * Формирует код привязки телеграм идентификатора
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function telegramBindStart(Request $request)
    {
        if ($request->user()->telegram_id)
            return response()->json(['message' => "У Вас уже имеется идентификатор"], 400);

        UserTelegramIdBind::whereUserPin($request->user()->pin)
            ->update(['deleted_at' => now()]);

        $unique = false;
        $code = rand(10000, 99999);

        while (!$unique) {

            $row = UserTelegramIdBind::where([
                ['code', $code],
                ['created_at', '>', now()->startOfDay()],
            ])->whereColumn('created_at', 'updated_at')->first();

            if ($row) {
                $code = rand(10000, 99999);
            } else {
                $unique = true;
            }
        }

        $row = UserTelegramIdBind::create([
            'user_pin' => $request->user()->pin,
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

        BindUserTelegram::dispatch($row->user_pin, $row->telegram_id);

        return response()->json([
            'message' => "Запрос успешно обработан",
        ]);
    }
}
