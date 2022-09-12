<?php

namespace App\Http\Controllers\Requests\Synhro;

use App\Events\AppUserPinEvent;
use App\Events\Requests\UpdateRequestEvent;
use App\Http\Controllers\Fines\Fines;
use App\Http\Controllers\Gates\GateBase64;
use App\Http\Controllers\Requests\AddRequest;
use App\Http\Controllers\Requests\RequestChange;
use App\Http\Controllers\Requests\RequestPins;
use App\Http\Controllers\Requests\Requests;
use App\Http\Controllers\Requests\RequestSectors;
use App\Http\Controllers\Users\DeveloperBot;
use App\Http\Controllers\Users\UserData;
use App\Models\Fine;
use App\Models\Gate;
use App\Models\GateSmsCount;
use App\Models\RequestsAutoChangeCount;
use App\Models\RequestsRow;
use App\Models\RequestsStory;
use App\Models\RequestsStoryStatus;
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
        "fineRemove", // Удаление штрафа
        "fineRestore", // Восстановление удаленного штрафа
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
                'method' => $type,
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
     * Хук автосмены статуса
     * 
     * @param  \Illumiante\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hoockSozvonToBk(Request $request)
    {
        $data = is_array($request->input('row')) ? $request->input('row') : [];

        if (!$row = RequestsRow::find($data['id'] ?? null))
            return response()->json(['message' => "Такой заявки нет"], 400);

        $status_old = $row->status_id;
        $row->status_id = $this->getStatusIdFromString("bk");
        $row->save();

        $story = RequestsStory::write(request(), $row);

        if ($status_old != $row->status_id) {
            RequestsStoryStatus::create([
                'story_id' => $story->id,
                'request_id' => $row->id,
                'status_old' => $status_old,
                'status_new' => $row->status_id,
                'created_pin' => optional(request()->user())->pin,
                'created_at' => now(),
            ]);
        }

        $row = Requests::getRequestRow($row);

        broadcast(new UpdateRequestEvent($row));

        $count = RequestsAutoChangeCount::firstOrNew([
            'pin' => (int) $row->pin,
            'date' => now()->format("Y-m-d"),
        ]);

        $count->count++;
        $count->save();

        broadcast(new AppUserPinEvent([
            'type' => "auto_change_count",
            'message' => "Автоматически изменен статус заявки {$row->id}",
            'count' => 1,
        ], $row->pin));

        return response()->json([
            'message' => "Обрботано нормально",
        ]);
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

    // eyJpdiI6IkNycUZYU21ZR0VXYXkzbHFDL2lEUXc9PSIsInZhbHVlIjoiWEZMS1VibXUyYnpGZmdjWjlpUmxZYU13blVSZEZFbW9jNHlvUGlSNEZmUmhUMk9ROFpSOTVqNmtZNHZsdm9KVXFGOFlGaWRDVW44VDJiZ2NRa2RuVGFHU1NZL28wb2JNNmRTTWY0ZU43RXVqREVtUVh2ZXlWWExzUTRmN0RDZXVmUDhOTEI1VW5vbS9ndTkzUXNCSzJPOTF4L2dTNXh1NUt3L2oxLyt2a2dwMlgxdkJva0R3cU1MU3JKS3ZXSW1lSXM4ZExQcEZ5U1N1NEdCR0xheSs4eFhzNWVoRUJ6VEFIRnYxeG43VXArVUR4RDlaTmdxdkppbXV3ZjB6QWkydFRxVTlMbUh3cmE1QzJkVHpCa2ltcllwTVRBa2REdWNsN1Nmalp0MUZZSkpGNkJ4d3NDOUgzUHV1c09CTmxad01VUEFraTRwMWpzK0F4Y0UwZWFZZUF1TVIvbERGSEt4eHNUYjJXWDdFK2VpWEg5dXVRQ0NNYkN2bzh2YnpvZDJ3Zm9wd2I2Y1FvZS8wclROUDE0RmhkakxkZHVpWlRMcWJBdm9Ka2gzNFhaVDRraWl6Y01ZUUY3Q3hxdmJsMnpTMG12alNPVDBTWmJxWHlFaUJzZGFCeXl2d0F1clJjd0w5b1Vha2poS1p4VTR1d3J6UklFVFptbVdvLytreW9CSzR2RjZ3OHAwdUFhMzJNYkxYTks0KzNSUWdBM2VrcWthY3RMbmdhQk01dVVCeDVtRUZZSXNoOE1JR1o5Y21GRHRxZWhlazg3emRGdzk1NU4rTnJwS0E4cUgzOWxPNVRneGtSWThjd2lJSDk1MCtueThPZmx3TE5IQzZwMEFOQittRVBkUnJDZFdxRUZzdmxFa0hoUWlUVEhJY2E2Z1czZ3BOVkZSSTNkSGFKeFA0ZnpXSXFwZVliWmdkZmtpS3hKN1g1czJQQmM1bjY2dy9oMHpGQ1p3QlNSNnBHL1VBd01iTjZTSmoydGR3NUl4Z1ZtS0t1OEI3aVdFZENudk5UUTI3bjVuczRUc2s5dUhqQkdOS0ozT0NuNVJoVU05dGhqRC9VZ2RxV0VrWHM3blRaVkdxYkxNKzhwVnRyVkJLdHlpYjdIUmlxdzV6cm9xWjA2bCt5NHZKdEVXYTZvUy9rT2hraGkzSGI0TUFrclBFNjlwN1hIRStwUms4cXRPY0wrOEhFeGVNaVRieTdqVzlLVG10U2JEVDRGVXJiVDNPY3RMY2NJeHVhRS9vTFhtcWRKMHZxdWNESytWaGtmaXlTMEl3ZGZWK0FTM2NLQmlSWUc2TmNvL1FBTDFFbWQvWlg5S0NKSFpKdkxwTHNUZmx0d3VjclVrWnBlWXZqMUdPRmRIZlhuTkxyR0lEc0VGR21hdjE4Y1hlQW1tRUNXWmFTRStSdE9ySkFGUEpxejU0ZjVYaGR1ejFVa1QyMEdVUXdhRkdsS3BYUXA0WlFCTGhibG9WUk5nZG5JYWlsU21ZL0hQM2ZBMG9HLzNCdjhEMEhJY1l2MnFGYWhIaXREVUFRSjR5ODNndyt5MFdOdFNDU1JSVmg0UEFsOWlYV0RDQ0w2M3NyWlBGWXBXeU8walVUbDRwSlZ4VGZ0MEpYd25SbXAwVyIsIm1hYyI6IjdhYjk2ZGEwNmZkZWZlZWRkYzJjNmY0M2MxMDA2OWU1ODU0NzE2NTBmZDgyMDM1NWU2N2M5NWJlNTY5ZGZhODIiLCJ0YWciOiIifQ==

    /**
     * Удаление штрафа
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hoockFineRemove(Request $request)
    {
        $data = is_array($request->input('row')) ? $request->input('row') : [];

        $row = Fine::where([
            ['pin', $data['pin'] ?? null],
            ['fine_date', $data['fine_date'] ?? null],
            ['pin_add', $data['pin_add'] ?? null],
            ['id_request', $data['id_request'] ?? null],
        ])->first();

        if (!$row)
            return response()->json(['message' => "Штраф не найден"], 400);

        $hoock_request = $this->httpRequest(['id' => $row->id], $data['pin_add'] ?? null);

        return (new Fines)->delete($hoock_request);
    }

    // eyJpdiI6IlR2UTlVelJwcCs0bHc0cHEzdnh0V3c9PSIsInZhbHVlIjoick5KMSt4KzMxb3FhSVhtblc3SVR1Z2ZIby9hVWljMk5Ydld6aGdlTnhPTTBhTENhdnFnTkV2NDhaVHRQZ1IwOGwyOTVlcDVpQlJCQTZHMHptOTNITjBTNUxRelRvMERtNnl6TVRKU0JsdHFtY0wxV3dFZzZ0L2hMaHZBOU1jeUVQWHQ4Q2JkNWVBNGJ1REM1cUwxN2lFS09iQy90WWtJVW00MGdZZTk5UHlJTlFpNnNmY2UrczdBQ0p2RWxKOXNHWkNaRWVQWHQ2c095N1UwaS9aOHlIOWwrWmIzWHFtR0JHSU5rM3NsaG5EY2hDakVSK0dvRjdwSE5VZk9TdXVQRHlpeTFHc0NWSng2enArcGxjYThHdGxhSjYvMXltWGlxeDJsc1g3Uk9yZkdFaFhZcytCcE5heFgzWk8rVkpuQVd5OWd1NXpZejNGc1dFT1o4bDRvZ1o5bTN2RFQyK2tFNUNjam54cWtQOVpMWW5rQklhMlpKdFJ6U1FqNGdhRkhKcXg2YmlzM3d2YlR4eGk5MkI3ejdkRlFXUGd2UVBYNTJFK2NObmhocjFQUHBVTUtZTTZXcUt1d0xRQXNMM05xWVd4bVRncW1UYVdUZWY3MlNCQ1ZQL0xQOCthYWlQMjZJNjhqRFBMYnh1Wko0bCtGaHFoM3pWTVVXN3BwNW1OT1NzMFVhRDNiTzA4YmhFY3dCVDlsRS8zNytWUFY2bllOam9mRU9LT1pSTncvZUpORTdtSXVUWDlGWkUvRGdxcmw5NXZYNklHSUxaMVZ3aTRyNXVGRkFDT3RMMTN0RURyTTNpRXlHRFZrdm5tazhFRWpLY0FYbWZlaG4wTk5zRVhuSUtFYXRaeDdIckozUE9PZ2wyMWlYVjlkaEttUkZQTDNqQzg5ZTBoaWJ3dmtRbDZJUzR0SmhMSFlTM0xnUFZ6b2xsVGFRWkNJUlMwR0loQjFSc3JsemkzV3JGb1dvOFMyUE9hN3BiOGxjMi8zWGZ0N3J3QzZiMGlIRUpOY3F2Z0g1ckhIdUxQYlVrYVRFRDVsbW1BSWUyeEczV21UdVNYMSswQzUyZWZXcWRXZE5NVm8rT2JRVlNrUi9SUWJDeEM1ZDRIT0c3dEJENFl0RFBnc255ZENLVmg3Tnk3MytRZmIyWTdoZjRaMy9EK3RXdHlKRHVFN2lIVXZGRU5uVFovaXJpZ3FRcGk5MlZDWFByYjZpRkY0aVptdkg4SjB6eVZ3K1dxVXltQTlnbVl2V0FoZTE3b1dvM1U4R1dsYnhMbWlvZEk2Z0ZXNEVrZytOa2QvY0N2OGdiU01FQ1pMT3Q0N250TllSUWV6TEFRUkt1RGFWRk1GOWdTa2hBSWhEMWM1eU9NdHlOTWljNUFpYWsrWWdQSFFQU0plNTArSjl6RmgrVXVzR09rWTJERS9ic2kxbDYxTTd1dGwrUUMvVWFoME12UVZ1cjdNVEhNanNhYUFHTzBRTW5RWWJaZXpSK09jWXh2czV5YzgzSERrPSIsIm1hYyI6IjZiYTc4NWY0ZDA3ZTdlYTUzODVhYTY3NjBiMTMyMTBkMmI5MDAwNzYyZjU4OWE4MjlkNTdmZjM1ZjM1YmExNDgiLCJ0YWciOiIifQ==

    /**
     * Восстановление удаленного штрафа
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    // public function hoockFineRestore(Request $request)
    // {
    //     return response()->json();
    // }

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

        if (is_array($fail_message['logs'] ?? null)) {

            foreach ($fail_message['logs'] as $row) {

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
        $row->sent_at = $send_at ?? (isset($data['updated_at']) ? now()->create($data['updated_at'])->addHours(3) : now());
        $row->response = $response;
        $row->failed_at = $data['fail_at'] ?? null;
        $row->created_at = isset($data['created_at']) ? now()->create($data['created_at'])->addHours(3) : now();

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
