<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Models\RequestsClient;
use App\Models\IncomingCall;
use App\Models\IncomingCallsToSource;
use App\Models\IncomingSecondCall;
use App\Models\Incomings\IncomingEvent;
use App\Models\Incomings\IncomingCallRequest;
use App\Models\Incomings\IncomingTextRequest;
use App\Models\Incomings\SipInternalExtension;
use App\Jobs\IncomingCallAsteriskJob;
use App\Jobs\IncomingRequestCallJob;
use App\Jobs\IncomingRequestTextJob;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;

class Events extends Controller
{
    /**
     * Промежуточный ключ
     * 
     * @var string
     */
    protected $key;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->key = base64_decode(str_replace("base64:", "", env('APP_KEY_IN')));
    }

    /**
     * Обработка входящего события
     * 
     * @param \Illuminate\Http\Request $request
     * @param null|string $type Параметр с типом запроса
     * @return \Illuminate\Http\JsonResponse
     */
    public function incoming(Request $request, $type = null)
    {
        $date = date("Y-m-d H:i:s");

        $response = array_merge($request->all(), [
            'message' => "Событие обработано",
            '_TYPE' => $type
        ]);

        if ($type == "call_asterisk") {
            $response['_JOB'] = $date;
            IncomingCallAsteriskJob::dispatch($request->call_id);
        } else if ($request->text and $text = IncomingTextRequest::find($request->text)) {
            $response['_JOB'] = $date;
            IncomingRequestTextJob::dispatch($text);
        } elseif ($request->call and $call = IncomingCallRequest::find($request->call)) {
            $response['_JOB'] = $date;
            IncomingRequestCallJob::dispatch($call);
        }

        return response()->json($response);
    }

    /**
     * Обработка события с текстовой заявкой
     * 
     * @param \App\Models\Incomings\IncomingTextRequest $row
     * @return \App\Models\Incomings\IncomingTextRequest
     */
    public function textEvent(IncomingTextRequest $row)
    {
        $date = date("Y-m-d H:i:s");

        // Расшифровка события
        $crypt = new Encrypter($this->key, config('app.cipher'));
        $data = $this->decrypt($row->event->request_data ?? null, $crypt);
        $row->event->request_data = $data;

        $recrypt = $this->encrypt($data); // Перешифровка данных

        // Проверка запроса и добавление его в заявку или очерель
        $queue = new Queues;
        $queue->checkEvent($row);

        $row->event->request_data = $recrypt;
        $row->event->recrypt = $date;
        $row->event->save();

        // Обновление данных по обработке текстового события
        $row->processed_at = $date;
        $row->save();

        return $row;
    }

    /**
     * Обработка звонка
     * 
     * @param \App\Models\Incomings\IncomingCallRequest $row
     */
    public function callEvent(IncomingCallRequest $row)
    {
        $date = date("Y-m-d H:i:s");

        // Расшифровка события
        $crypt = new Encrypter($this->key, config('app.cipher'));
        $data = $this->decrypt($row->event->request_data ?? null, $crypt);
        $row->event->request_data = $data;

        $recrypt = $this->encrypt($data); // Перешифровка данных

        $this->addRequestFromCall($row); // Добавление заявки

        $row->event->request_data = $recrypt;
        $row->event->recrypt = $date;
        $row->event->save();

        // Обновление данных по обработке текстового события
        $row->processed_at = $date;
        $row->save();

        return $row;
    }

    /**
     * Добавление заявки из звонка
     * 
     * @param \App\Models\Incomings\IncomingCallRequest $row
     * @return null
     */
    public function addRequestFromCall($row)
    {
        // Запись входящего звонка
        $incoming = IncomingCall::create([
            'phone' => $this->encrypt($row->event->request_data->phone),
            'sip' => $row->event->request_data->sip,
        ]);

        // Слушатель сип номеров
        $sip = IncomingCallsToSource::where([
            ['extension', $row->event->request_data->sip],
            ['on_work', 1]
        ])
            ->first();

        if (!$sip) {

            $incoming->failed = date("Y-m-d H:i:s");
            $incoming->save();

            broadcast(new \App\Events\IncomingCalls($incoming));

            return null;
        }

        // Добавление заявки
        $request = new Request(
            query: (array) [
                'phone' => $row->event->request_data->phone,
                'myPhone' => $sip->phone,
            ],
            server: [
                'REMOTE_ADDR' => $row->event->ip,
                'HTTP_USER_AGENT' => $row->event->user_agent,
            ]
        );

        $addRequest = new AddRequest($request);
        $addRequest->add($request);

        $incoming->added = date("Y-m-d H:i:s");
        $incoming->save();

        $sip->added++;
        $sip->save();

        broadcast(new \App\Events\IncomingCalls($incoming));

        return null;
    }

    /**
     * Повтрная обработка входящего звонка
     * 
     * @param \App\Models\IncomingCall $incoming
     * @param null|int $pin
     * @param null|string $ip
     * @param null|string $user_agent
     * @return null
     */
    public static function retryAddRequestFromCall(IncomingCall $incoming, $pin, $ip, $user_agent)
    {

        // Слушатель сип номеров
        $sip = IncomingCallsToSource::where([
            ['extension', $incoming->sip],
            ['on_work', 1]
        ])->first();

        if (!$sip) {

            $incoming->failed = date("Y-m-d H:i:s");
            $incoming->added = null;
            $incoming->save();

            broadcast(new \App\Events\IncomingCalls($incoming, true));

            return null;
        }

        // Добавление заявки
        $request = new Request(
            query: [
                'phone' => parent::decrypt($incoming->phone),
                'myPhone' => $sip->phone,
                'retry' => true,
                'pin' => $pin,
                'manual' => true,
            ],
            server: [
                'REMOTE_ADDR' => $ip,
                'HTTP_USER_AGENT' => $user_agent,
            ]
        );

        $addRequest = new AddRequest($request);
        $response = $addRequest->add($request);

        if ($response['done'] == "fail") {

            $incoming->added = null;
            $incoming->failed = date("Y-m-d H:i:s");
            $incoming->save();

            broadcast(new \App\Events\IncomingCalls($incoming, true));

            return null;
        }

        $incoming->added = date("Y-m-d H:i:s");
        $incoming->failed = null;
        $incoming->save();

        $sip->added++;
        $sip->save();

        return null;
    }

    /**
     * Просмотр входящих событий
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return view
     */
    public function eventView(Request $request, int $id)
    {
        $row = \App\Models\Incomings\IncomingEvent::find($id);

        if ($row and self::checkIpForDecrypt($request->ip())) {

            if (!$row->recrypt)
                $crypt = new Encrypter($this->key, config('app.cipher'));

            $row->request_data = parent::decrypt($row->request_data, $crypt ?? null);
        }

        return view('event', [
            'row' => $row ? $row->toArray() : null,
            'next' => $id + 1,
            'back' => $id - 1,
            'id' => $id,
            'ip' => $request->ip(),
            'max' => \App\Models\Incomings\IncomingEvent::max('id'),
        ]);
    }

    /**
     * Проверка IP для вывод расшифровывания события
     * 
     * @param string $ip
     * @return bool
     */
    public static function checkIpForDecrypt(string $ip): bool
    {
        if (in_array($ip, ['91.230.53.106']))
            return true;

        $parts = [
            '192.168.0.',
            '172.16.255.',
        ];

        foreach ($parts as $part) {
            if (strripos($ip, $part) !== false)
                return true;
        }

        return false;
    }

    /**
     * Автоматическое назначение оператора на заявку
     * 
     * @param int $id
     * @return null|bool|int
     * 
     * @todo Доделать обработку звонка для автоматического присвоения заявки оператору
     */
    public function incomingCallAsterisk($id = null)
    {
        if (!$event = IncomingEvent::find($id))
            return null;

        // Расшифровка данных при помощи внешнего ключа
        if (!$event->recrypt)
            $crypt = new Encrypter($this->key, config('app.cipher'));

        // Расшифровка данных
        $data = parent::decryptSetType($event->request_data ?? null, $crypt ?? null);

        // Номер телефона и его хэш
        $phone = parent::checkPhone($data->Number ?? null);
        $hash = parent::hashPhone($data->Number ?? null);

        if (!$phone)
            return null;

        // Поиск клиента с номером телефона
        $this->client = RequestsClient::firstOrCreate(
            ['hash' => $hash],
            ['phone' => parent::encrypt($phone)],
        );

        // Внутренний номер ip-телефонии
        $extension = $data->extension ?? null;

        // Поиск внутреннего адреса номера телефонии
        if (!$internal = SipInternalExtension::where('extension', $extension)->first())
            return null;

        // Обработка вторичного звонка
        // Настройка идентификатор вторичного звонка указана в таблице внутренних номеров
        if ($internal->for_in == 1)
            return $this->incomingSecondCallAsterisk();

        return null;
    }

    /**
     * Подъем заявки вторичного звонка
     * 
     * @return null
     */
    public function incomingSecondCallAsterisk()
    {
        $this->callDate = date("Y-m-d");

        if (count($this->client->requests))
            return $this->createIncomingCallForClientRequests($this->client);

        IncomingSecondCall::create([
            'client_id' => $this->client->id,
            'call_date' => $this->callDate,
        ]);

        return null;
    }

    /**
     * Создание строк для нескольки звонков
     * 
     * @param \App\Models\RequestsClient $client
     * @return null
     */
    public function createIncomingCallForClientRequests($client)
    {
        foreach ($client->requests as $request) {
            IncomingSecondCall::create([
                'client_id' => $client->id,
                'request_id' => $request->id,
                'call_date' => $this->callDate,
            ]);
        }

        return null;
    }
}
