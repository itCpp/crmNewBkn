<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CreatedNewRequest implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Данные о заявке
     *
     * @var object
     */
    public $row;

    /**
     * Идентификатор удаленной заявки
     *
     * @var bool|int
     */
    public $zeroing;

    /**
     * Create a new event instance.
     *
     * @param object $row
     * @param bool|int
     * @return void
     */
    public function __construct($row, $zeroing = false)
    {
        $this->row = $row;
        $this->zeroing = $zeroing;
    }

    /**
     * Данные для трансляции
     *
     * @return array
     */
    public function broadcastWith()
    {
        if (isset($this->row->clients))
            unset($this->row->clients);

        if (isset($this->row->permits))
            unset($this->row->permits);

        return [
            'row' => $this->row,
            'zeroing' => $this->zeroing,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('App.Requests');
    }
}
