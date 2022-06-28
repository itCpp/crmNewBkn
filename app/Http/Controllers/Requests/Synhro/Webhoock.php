<?php

namespace App\Http\Controllers\Requests\Synhro;

use App\Http\Controllers\Requests\AddRequest;
use App\Models\RequestsRow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Webhoock extends Merge
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
     * Объект обработки старых заявок
     * 
     * @var \App\Http\Controllers\Dev\RequestsMerge
     */
    protected $merge;

    /**
     * Инициализация объекта
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__constrict();
    }

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

    /**
     * Запрос на создание заявки
     * 
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hoockCreate(Request $request)
    {
        return $this->createOrUpdateHoock($request);
    }

    /**
     * Запрос на обновление заявки
     * 
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hoockUpdate(Request $request)
    {
        return $this->createOrUpdateHoock($request);
    }

    /**
     * Обработка хуков создания и обновления заявки
     * 
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrUpdateHoock(Request $request)
    {
        $data = $request->row ?? [];

        if (!is_array($data))
            $data = [];

        /** Экземпляр модели старой заявки */
        $row = $this->getCrmRequestRow($request->row);
        $query_type = $this->getQueryType($row);

        if (!isset($data['phone']))
            $data['phone'] = $request->phone;

        $add_request = new Request(query: $data);
        $add_request->responseData = true;
        $add_request->fromWebhoock = true;

        $data = (new AddRequest($add_request))->add();

        return response()->json($data);
    }
}
