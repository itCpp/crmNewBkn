<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuthDone implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Данные авторизации
     * 
     * @var object
     */
    public $data;

    /**
     * Флаг полодительного решения
     * 
     * @var bool
     */
    public $done;

    /**
     * Create a new event instance.
     *
     * @param object $data
     * @param bool $done
     * @return void
     */
    public function __construct($data, $done = false)
    {
        $this->data = $data;
        $this->done = $done;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel("App.Auth.{$this->data->id}");
    }
}
