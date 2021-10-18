<?php

namespace App\Events;

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
    public $row;

    /**
     * Персональный идентификационный номер сотрудника
     * 
     * @var int|string;
     */
    public $pin;

    /**
     * Флаг необходимости удаления заявки
     * 
     * @var bool
     */
    public $drop;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($row, $pin, $drop = false)
    {
        $this->row = $row;
        $this->pin = $pin;
        $this->drop = $drop;
    }

    /**
     * Данные для трансляции
     *
     * @return array
     */
    public function broadcastWith()
    {
        // // Удаление данных о клиенте
        // if (isset($this->row->clients))
        //     unset($this->row->clients);

        // // Удаление данных о правах сотрудника по заявке
        // if (isset($this->row->permits))
        //     unset($this->row->permits);

        // // Дополнение информации о заявке
        // if (!$this->drop) {
        //     if ($user = Users::findUserPin($this->pin)) {
        //         // Права пользователя по заявкам
        //         $this->row->permits = $user->getListPermits(RequestStart::$permitsList);

        //         // Информация по клиентам
        //         $this->row->clients = Requests::getClientPhones(
        //             RequestsRow::find($this->row->id),
        //             $this->row->permits->clients_show_phone
        //         );
        //     }
        // }

        return [
            'row' => $this->row->id,
            'drop' => $this->drop,
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
