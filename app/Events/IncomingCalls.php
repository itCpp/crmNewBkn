<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncomingCalls implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Данные о входящем звонке
     * 
     * @var object
     */
    public $data;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Данные для трансляции
     *
     * @return array
     */
    public function broadcastWith()
    {
        $data = $this->data->toArray();
        $data['phone'] = \App\Http\Controllers\Controller::decrypt($data['phone']);
        $data['phone'] = \App\Http\Controllers\Controller::checkPhone($data['phone'], 2);

        return [
            'data' => $data,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('App.Admin.Calls');
    }
}
