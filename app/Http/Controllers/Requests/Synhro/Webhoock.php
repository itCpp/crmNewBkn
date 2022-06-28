<?php

namespace App\Http\Controllers\Requests\Synhro;

use App\Http\Controllers\Requests\AddRequest;
use App\Http\Controllers\Requests\RequestChange;
use App\Http\Controllers\Requests\RequestPins;
use App\Http\Controllers\Users\DeveloperBot;
use App\Http\Controllers\Users\UserData;
use App\Models\RequestsRow;
use App\Models\User;
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

        $this->logger = Log::channel('webhoock_access');
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

        $this->logger->info(($virify ? "ALLOW" : "DENIED") . " webhoock/{$type}", [
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
        $data = $request->input('request') ?? [];

        if (!is_array($data))
            $data = [];

        /** Экземпляр модели старой заявки */
        $row = $this->getCrmRequestRow($request->row);
        $data['query_type'] = $this->getQueryType($row);

        if (!isset($data['phone']))
            $data['phone'] = $request->phone;

        if ($data['query_type'] == "text") {

            if (!isset($data['site']))
                $data['site'] = $row->typeSiteLink;
        } else if ($data['query_type'] == "call") {

            if (!isset($data['myPhone']))
                $data['myPhone'] = $row->myPhone;
        }

        $add_request = new Request(query: $data);
        $add_request->responseData = true;
        $add_request->fromWebhoock = true;

        $data = (new AddRequest($add_request))->add();

        return response()->json($data);
    }

    /**
     * Создает экземпляр объекта запроса
     * 
     * @param  array $data
     * @param  string|int|null $pin
     * @return \Illuminate\Http\Request
     */
    public function createRequest($data = [], $pin = null)
    {
        $request = new Request($data);

        $request->setUserResolver(function () use ($pin) {

            $user = User::wherePin($pin)->first();

            return $user ? new UserData($user) : (new DeveloperBot)();
        });

        return $request;
    }

    /**
     * Проверяет и/или создает строку заявки на основе старой
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \App\Models\RequestsRow
     */
    public function checkRequestId(Request $request)
    {
        if (!$row = RequestsRow::find($request->row['id'] ?? null))
            $row = $this->createOrUpdateRequestFromOld($request);

        return $row;
    }

    /**
     * Назначение оператора
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hoockPin(Request $request)
    {
        $query = is_array($request->input('request')) ? $request->input('request') : [];
        $query['addr'] = $query['address'] ?? null;
        $query['user'] = $this->getOperatorUserId($query['pin']);

        $this->checkRequestId($request);

        $hoock_request = $this->createRequest($query, $request->pin);

        return RequestPins::setPin($hoock_request);
    }

    /**
     * Обработка сохранений
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hoockSave(Request $request)
    {
        $data = is_array($request->input('request')) ? $request->input('request') : [];

        /** Идентификатор заявки */
        $query['id'] = $data['id'] ?? null;

        /** Идентификатор статуса */
        $query['status_id'] = $this->getStatusIdFromString($data['state'] ?? null);

        /** Дата и время события */
        $query['event_date'] = $data['rdate'] ?? null;
        $query['event_time'] = $data['time'] ?? null;

        /** ФИО клиента */
        $query['client_name'] = $data['name'] ?? null;

        /** Тематика */
        $query['theme'] = $data['theme'] ?? null;

        /** Город проживания */
        $query['region'] = $data['region'] ?? null;

        /** Комментарии */
        $query['comment'] = $data['comment'] ?? null;
        $query['comment_urist'] = $data['uristComment'] ?? null;

        /** Адресс */
        $query['address'] = $data['address'] ?? null;

        $this->checkRequestId($request);

        $hoock_request = $this->createRequest($query, $request->pin);

        return RequestChange::save($hoock_request);
    }
}
