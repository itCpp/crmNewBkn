<?php

namespace App\Events\Requests;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateRequestEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Данные о заявке
     * Подготовленный объект для вывода
     * @see \App\Http\Controllers\Requests\Requests::getRequestRow
     * 
     * @var object
     */
    public $row;

    /**
     * Флаг отправки уведомления только другим
     * 
     * @var bool
     */
    public $toOthers;

    /**
     * Create a new event instance.
     *
     * @param object $row
     * @param bool $toOthers
     * @return void
     */
    public function __construct($row, $toOthers = true)
    {
        $this->row = $row;
        $this->toOthers = $toOthers;
    }
}
