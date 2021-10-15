<?php

namespace App\Events\Requests;

use App\Models\RequestsRow;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AddRequestEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Данные по заявке
     * 
     * @var \App\Models\RequestsRow
     */
    public $row;

    /**
     * Массив данных о работе добавления
     * 
     * @param \App\Models\RequestsRow $row
     * @param array $response
     * @var array
     */
    public $response;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(RequestsRow $row, $response)
    {
        $this->row = $row;
        $this->response = $response;
    }
}
