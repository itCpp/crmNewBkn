<?php

namespace App\Http\Controllers\Queues;

use App\Models\RequestsQueue;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Requests\AddRequest;
use Illuminate\Http\Request;

/**
 * Добавление заявки из очереди при автоматической настройки или вручную
 * 
 * @method add()
 */
class QueueProcessings extends Controller
{
    /**
     * Экземпляр модели очереди
     * 
     * @var \App\Models\RequestsQueue
     */
    protected $row;

    /**
     * Создание экземпляра объекта
     * 
     * @param \App\Models\RequestsQueue|int $row
     * @return void
     */
    public function __construct($row)
    {
        if (!$row instanceof RequestsQueue)
            $row = RequestsQueue::find($row);

        $this->row = $row;
    }

    /**
     * Процесс добавления заявки
     * 
     * @return array
     */
    public function add()
    {
        $request = new Request(
            query: array_merge(
                (array) $this->decrypt($this->row->request_data),
                ['responseData' => true]
            ),
            server: [
                'REMOTE_ADDR' => $this->row->ip,
                'HTTP_USER_AGENT' => $this->row->user_agent,
            ]
        );

        $added = (new AddRequest($request))->add();

        $this->row->request_id = $added['requestId'] ?? null;
        $this->row->done_pin = "AUTO";
        $this->row->done_type = 1;
        $this->row->done_at = now();

        $this->row->save();

        return $added;
    }
}
