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

    protected $addToPin = null;
    protected $removeToPin = null;

    /**
     * Create a new event instance.
     *
     * @param object $row
     * @param bool|int
     * @return void
     */
    public function __construct($row)
    {
        $this->row = $row;
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

        // Флаг добавления заявки для оператора
        if (isset($this->row->newPin)) {
            if ($add = ($this->row->newPin == $this->row->pin))
                $this->addToPin = $this->row->newPin;
        }

        // Флаг удаления заявки у оператора
        if (isset($this->row->oldPin)) {
            if ($remove = ($this->row->oldPin != $this->row->pin))
                $this->removeToPin = $this->row->oldPin;
        }

        return [
            'row' => $this->row,
            'add' => $add ?? null,
            'remove' => $remove ?? null,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        $channels = [new PrivateChannel('App.Requests')];

        if ($this->addToPin)
            $channels[] = new PrivateChannel("App.Requests.{$this->addToPin}");

        if ($this->removeToPin)
            $channels[] = new PrivateChannel("App.Requests.{$this->removeToPin}");

        return $channels;
    }
}
