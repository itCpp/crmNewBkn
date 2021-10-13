<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Models\Incomings\IncomingCallRequest;
use App\Models\Incomings\IncomingTextRequest;
use App\Models\IncomingCall;
use App\Models\IncomingCallsToSource;
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
     * @return response
     */
    public function incoming(Request $request)
    {
        $response = $request->all();
        $response['message'] = "Событие обработано";

        if ($request->text and $text = IncomingTextRequest::find($request->text)) {
            $response['job'] = now();
            IncomingRequestTextJob::dispatch($text);
        } elseif ($request->call and $call = IncomingCallRequest::find($request->call)) {
            $response['job'] = now();
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
        $date = now();

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
        $date = now();

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

            $incoming->failed = now();
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

        $incoming->added = now();
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

            $incoming->failed = now();
            $incoming->added = null;
            $incoming->save();

            broadcast(new \App\Events\IncomingCalls($incoming, true));

            return null;
        }

        // Добавление заявки
        $request = new Request(
            query: (array) [
                'phone' => parent::decrypt($incoming->phone),
                'myPhone' => $sip->phone,
                'retry' => true,
                'pin' => $pin
            ],
            server: [
                'REMOTE_ADDR' => $ip,
                'HTTP_USER_AGENT' => $user_agent,
            ]
        );

        $addRequest = new AddRequest($request);
        $addRequest->add($request);

        $incoming->added = now();
        $incoming->failed = null;
        $incoming->save();

        $sip->added++;
        $sip->save();

        broadcast(new \App\Events\IncomingCalls($incoming, true));

        return null;
    }
}
