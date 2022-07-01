<?php

namespace App\Http\Controllers\Requests\Synhro;

use App\Http\Controllers\Fines\Fines;
use App\Http\Controllers\Gates\GateBase64;
use App\Http\Controllers\Requests\AddRequest;
use App\Http\Controllers\Requests\RequestChange;
use App\Http\Controllers\Requests\RequestPins;
use App\Http\Controllers\Requests\RequestSectors;
use App\Http\Controllers\Users\DeveloperBot;
use App\Http\Controllers\Users\UserData;
use App\Models\Gate;
use App\Models\GateSmsCount;
use App\Models\RequestsRow;
use App\Models\SmsMessage;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        "fineAdd", // Создание штрафа
        "firstComment", // Запись первичного комментария
        "hide", // Скрытие заявки со статусом из необработанных
        "pin", // Смена оператора
        "pinOwn", // Присвоение заявки себе
        "save", // Изменение данных
        "sbComment", // Комментарий службы безопасности
        "smsOut", // Исходящее СМС
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

        try {
            $encrypt = encrypt([
                'real_ip' => $request->header('X-Real-Ip') ?: $request->ip(),
                'ip' => $request->ip(),
                'token' => $token,
                'request' => $request->all(),
                'headers' => $request->header(),
            ]);

            $id = $request->input('row')['id'] ?? null;

            $this->logger
                ->info(($virify ? "ALLOW" : "DENIED") . " [{$id}] [{$type}] {$encrypt}");
        } catch (Exception) {
        }

        if (!env('NEW_CRM_OFF', true))
            throw new Exception("Новая ЦРМ включена в работу");

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
        $add_request->webhoockRowCreated = true;
        // $add_request->webhoockRowCreated = RequestsRow::whereId($row->id ?? null)->count() == 0;
        $add_request->webhoockRow = $this->createOrUpdateRequestFromOld($request);

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
    public function httpRequest($data = [], $pin = null)
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

        $hoock_request = $this->httpRequest($query, $request->pin);

        return RequestPins::setPin($hoock_request);
    }

    /**
     * Присвоение заявки
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hoockPinOwn(Request $request)
    {
        $query = is_array($request->input('request')) ? $request->input('request') : [];
        $query['user'] = $this->getOperatorUserId($request->input('pin'));
        $query['toOwn'] = $request->input('pin');

        $this->checkRequestId($request);

        $hoock_request = $this->httpRequest($query, $request->pin);

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
        $this->checkRequestId($request);

        $data = is_array($request->input('request')) ? $request->input('request') : [];

        /** Идентификатор заявки */
        $query['id'] = $data['id'] ?? null;

        /** Идентификатор статуса */
        $query['status_id'] = $this->getStatusIdFromString($data['state'] ?? ($request->row['state'] ?? null));

        /** Дата и время события */
        $query['event_date'] = $data['rdate'] ?? ($request->row['rdate'] ?? null);
        $query['event_time'] = $data['time'] ?? ($request->row['time'] ?? null);

        /** ФИО клиента */
        $query['client_name'] = $data['name'] ?? ($request->row['name'] ?? null);

        /** Тематика */
        $query['theme'] = $data['theme'] ?? ($request->row['theme'] ?? null);

        /** Город проживания */
        $query['region'] = $data['region'] ?? ($request->row['region'] ?? null);

        /** Комментарии */
        $query['comment'] = $data['comment'] ?? ($request->row['comment'] ?? null);
        $query['comment_urist'] = $data['uristComment'] ?? ($request->row['uristComment'] ?? null);

        /** Адрес */
        $query['address'] = $data['address'] ?? ($request->row['address'] ?? null);

        $hoock_request = $this->httpRequest($query, $request->pin);

        return RequestChange::save($hoock_request);
    }

    /**
     * Смена сектора
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hoockChangeSector(Request $request)
    {
        $this->checkRequestId($request);

        $data = is_array($request->input('request')) ? $request->input('request') : [];

        $query['id'] = $data['id'] ?? null;
        $query['sector'] = $this->getSectorIdFromId($data['sector'] ?? null);

        $hoock_request = $this->httpRequest($query, $request->pin);

        return RequestSectors::setSector($hoock_request);
    }

    /**
     * Установка первичного комментария из ячейки
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hoockFirstComment(Request $request)
    {
        $this->checkRequestId($request);

        $data = is_array($request->input('request')) ? $request->input('request') : [];

        $query['id'] = $data['id'] ?? null;
        $query['comment_first'] = trim($data['first_comment'] ?? "");

        $hoock_request = $this->httpRequest($query, $request->pin);
        $hoock_request->__cell = "commentFirst";

        return RequestChange::saveCell($hoock_request);
    }

    /**
     * Установка тематики заявки из ячейки
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hoockTheme(Request $request)
    {
        $this->checkRequestId($request);

        $data = is_array($request->input('request')) ? $request->input('request') : [];

        $query['id'] = $data['id'] ?? null;
        $query['theme'] = $data['theme'] ?? null;

        $hoock_request = $this->httpRequest($query, $request->pin);
        $hoock_request->__cell = "theme";

        return RequestChange::saveCell($hoock_request);
    }

    /**
     * Скрытие без обработки
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hoockHide(Request $request)
    {
        $this->checkRequestId($request);

        $data = is_array($request->input('request')) ? $request->input('request') : [];

        $query['id'] = $data['id'] ?? null;

        $hoock_request = $this->httpRequest($query, $request->pin);

        return RequestChange::hideUplift($hoock_request);
    }

    /**
     * Создание штрафа сотруднику
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hoockFineAdd(Request $request)
    {
        $data = is_array($request->input('row')) ? $request->input('row') : [];

        $query['fine'] = $data['fine'] ?? 0;
        $query['pin'] = $data['pin'] ?? null;
        $query['comment'] = $data['comment'] ?? null;
        $query['request_id'] = $data['id_request'] ?? null;
        $query['date'] = $data['fine_date'] ?? null;
        $query['is_autofine'] = $data['auto_fine'] ?? null;


        $hoock_request = $this->httpRequest($query, $data['pin_add'] ?? null);

        return (new Fines)->create($hoock_request);
    }

    /**
     * Исходящее СМС
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hoockSmsOut(Request $request)
    {
        $data = is_array($request->input('row')) ? $request->input('row') : [];

        $fail_message = json_decode($data['fail_message'], true);
        $response = (object) [];

        if (is_array($fail_message)) {

            foreach ($fail_message as $row) {
                if (isset($row['response'])) {
                    $send_at = $row['date'] ?? null;
                    $response->Message = $row['response']['Message'] ?? null;
                    $response->Response = $row['response']['Response'] ?? null;
                }
            }

            if (isset($fail_message['status']) and !isset($response->Response))
                $response->Response = $fail_message['status'];
        }

        if (($response->Response ?? null) == "Success")
            $response->ResponseCode = 200;

        $base64 = new GateBase64;
        $row = new SmsMessage;

        $row->message_id = Str::orderedUuid();
        $row->gate = Gate::whereAddr($data['gate'] ?? null)->first()->id ?? null;
        $row->channel = $data['channel'] ?? null;
        $row->created_pin = $data['pin'] ?? null;
        $row->phone = $this->encrypt($this->checkPhone($data['phone'] ?? null));
        $row->message = $base64->encode($data['text'] ?? null);
        $row->direction = "out";
        $row->sent_at = $send_at ?? ($data['updated_at'] ?? now()->format("Y-m-d H:i:s"));
        $row->response = $response;
        $row->failed_at = $data['fail_at'] ?? null;
        $row->created_at = $data['created_at'] ?? now();

        $row->save();

        if ($data['id_request'] ?? null)
            $row->requests()->attach($data['id_request']);

        $count = GateSmsCount::firstOrNew([
            'gate_id' => $row->gate,
            'channel_id' => $row->channel,
            'date' => now()->format("Y-m-d"),
        ]);

        $count->count++;
        $count->save();

        return response()->json(['message' => "Сообщение принято"]);
    }
}
