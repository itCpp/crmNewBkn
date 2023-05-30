<?php

namespace App\Events\Requests;

use App\Http\Controllers\Users\Users;
use App\Http\Controllers\Requests\Requests;
use App\Http\Controllers\Requests\RequestStart;
use App\Models\RequestsRow;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateRequestRowForPin implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Полученные данные по заявке
     * 
     * @var object
     */
    protected $row;

    /**
     * Персональный идентификационный номер сотрудника
     * 
     * @var int|string;
     */
    protected $pin;

    /**
     * Флаг необходимости удаления заявки
     * 
     * @var bool
     */
    protected $drop;

    /**
     * Флаг присвоения заявки
     * 
     * @var bool
     */
    protected $own;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($row, $pin, $drop = false, $own = false)
    {
        $this->row = $row;
        $this->pin = $pin;
        $this->drop = $drop;
        $this->own = $own;
    }

    /**
     * Данные для трансляции
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'row' => $this->row->id,
            'drop' => $this->drop,
            'own' => $this->own,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel("App.Requests.{$this->pin}");
    }
}
