<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Models\Incomings\IncomingEvent;
use App\Models\Incomings\IncomingTextRequest;
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

        if ($request->text AND $text = IncomingTextRequest::find($request->text)) {
            $response['job'] = now();
            IncomingRequestTextJob::dispatch($text);
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

        dd($data, $row);

        // $event->request_data = $this->encrypt($data);
        $event->recrypt = $date;
        $event->save();

        // Обновление данных по обработке текстового события
        $row->processed_at = $date;
        $row->save();

        return $row;

    }

}
