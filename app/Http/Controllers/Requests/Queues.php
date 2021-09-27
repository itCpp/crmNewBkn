<?php

namespace App\Http\Controllers\Requests;

use App\Http\Controllers\Requests\AddRequest;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings;
use App\Models\Incomings\IncomingTextRequest;
use App\Models\RequestsQueue;
use App\Models\RequestsSourcesResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class Queues extends Controller
{

    /**
     * Глобальные настройки очереди
     * 
     * @var \App\Http\Controllers\Settings
     */
    protected $settings;

    /**
     * Иницифлизация объекта
     * 
     * @return void
     */
    public function __construct()
    {
        $this->settings = new Settings(['TEXT_REQUEST_AUTO_ADD']);
    }

    /**
     * Обработка события текстовой заявки
     * 
     * @param \App\Models\Incomings\IncomingTextRequest $row
     * @return bool
     */
    public function checkEvent(IncomingTextRequest $row)
    {

        // Глобавльная настрока на автодобавлние
        if (!$this->settings->TEXT_REQUEST_AUTO_ADD) {

            // Настройка источника на автодобавление
            if (!$this->checkSourceForAutoDone($row->event->request_data->site ?? null)) {

                // Создание очереди
                RequestsQueue::create([
                    'phone' => Crypt::encryptString($row->event->request_data->phone ?? null),
                    'name' => Crypt::encryptString($row->event->request_data->name ?? null),
                    'comment' => $row->event->request_data->comment ?? null,
                    'ip' => $row->event->ip ?? null,
                    'site' => $row->event->request_data->site ?? null,
                    'gets' => $row->event->request_data->__GETS ?? [],
                ]);

                return true;

            }

        }

        $this->autoAddRequest($row);

        return false;

    }

    /**
     * Проверка источника на автоматическое добавление заявки
     * 
     * @param string $val
     * @return bool
     */
    public function checkSourceForAutoDone($val)
    {

        if (!$resource = RequestsSourcesResource::where('val', $val)->first())
            return false;

        if (!$resource->source->auto_done_text_queue ?? null)
            return false;

        return true;
        
    }

    /**
     * Автоматическое добавление заявки
     * 
     * @param \App\Models\Incomings\IncomingTextRequest $row
     * @return $this
     */
    public function autoAddRequest(IncomingTextRequest $row)
    {

        $request = new Request(
            query: (array) $row->event->request_data,
            server: [
                'REMOTE_ADDR' => $row->event->ip,
                'HTTP_USER_AGENT' => $row->event->user_agent,
            ]
        );

        $addRequest = new AddRequest($request);
        $addRequest->add($request);

        return $this;

    }
    
}
