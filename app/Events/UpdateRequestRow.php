<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateRequestRow implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Данные о заявке
     *
     * @var object
     */
    public $row;

    /**
     * Персональный идентификационный номер сотрудника, который должен проигнорировать уведомление
     * 
     * @var array
     */
    public $toExclude;

    /**
     * Create a new event instance.
     *
     * @param object $row
     * @param bool|int
     * @return void
     */
    public function __construct($row, $toExclude = [])
    {
        $this->row = $row;
        $this->toExclude = $toExclude;
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
            'toExclude' => $this->toExclude,
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
