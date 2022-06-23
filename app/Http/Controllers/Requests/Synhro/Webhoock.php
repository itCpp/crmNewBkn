<?php

namespace App\Http\Controllers\Requests\Synhro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Webhoock extends Controller
{
    /**
     * Существующие методы
     * 
     * @var array
     */
    protected $methods = [
        "addPhone", // Запись дополнительного телефона
        "changeSector", // Смена сектора
        "create", // Создание новой заявки
        "firstComment", // Запись первичного комментария
        "hide", // Скрытие заявки со статусом из необработанных
        "pin", // Смена оператора
        "save", // Изменение данных
        "sbComment", // Комментарий службы безопасности
        "theme", // Изменение темы
        "update", // Обновление заявки при поступлении нового обращения
    ];

    /**
     * Handle calls to missing methods on the controller.
     * 
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return response()->json([
            'message' => "Method [{$method}] not found"
        ], 400);
    }

    /**
     * Првоерка токена доступа
     * 
     * @param  string $token
     * @return boolean
     */
    public function virifyToken($token)
    {
        return env("ACCESS_TOKEN_SYNHRO") == $token;
    }

    /**
     * Обработка входящего запроса
     * 
     * @param  \Illuminate\Http\Request $request
     * @param  string $token
     * @param  string $type
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $token, $type)
    {
        $virify = $this->virifyToken($token);

        Log::channel('webhoock_access')->info(($virify ? "ALLOW" : "DENIED") . " webhoock/{$type}", [
            'real_ip' => $request->header('X-Real-Ip') ?: $request->ip(),
            'ip' => $request->ip(),
            'token' => $token,
            'request' => $request->all(),
            'headers' => $request->header(),
        ]);

        if (!$virify)
            return response()->json(['message' => "Permission denied"], 403);

        $method = "hoock" . ucfirst($type);

        return $this->$method($request);
    }
}
