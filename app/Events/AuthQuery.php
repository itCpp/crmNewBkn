<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuthQuery implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Идентификатор коллцентра
     * 
     * @var int|null
     */
    protected $callcenter = 0;

    /**
     * Идентификатор сектора
     * 
     * @var int|null
     */
    protected $sector = 0;

    /**
     * Данные запроса на авторизацию
     * 
     * @var \App\Models\UserAuthQuery $query
     */
    public $query;

    /**
     * Данные пользователя
     * 
     * @var \App\Http\Controllers\Users\UserData
     */
    public $user;

    /**
     * Время запроса
     * 
     * @var string
     */
    public $date;

    /**
     * Флаг отмены запроса
     * 
     * @var bool
     */
    public $cancel = false;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\UserAuthQuery $query
     * @param \App\Http\Controllers\Users\UserData $user
     * @return void
     */
    public function __construct($query, $user)
    {
        $this->query = $query;

        $this->callcenter = $query->callcenter_id;
        $this->sector = $query->sector_id;

        $this->user = $user;
        $this->query->user = $user;

        $this->cancel = $this->query->done_at ? true : false;
    }

    /**
     * Данные для трансляции
     *
     * @return array
     */
    public function broadcastWith()
    {
        $data = $this->query->toArray();

        $data['user'] = $this->user;
        $data['date'] = date("d.m.Y H:i:s", strtotime($this->query->created_at));

        return [
            'query' => $data,
            'cancel' => $this->cancel,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {

        // Уведомление только для своего сектора
        $channels[] = "App.Admin.AuthQueries.{$this->callcenter}.{$this->sector}";

        // Уведомление для всех коллцентров и секторов
        if ($this->callcenter)
            $channels[] = "App.Admin.AuthQueries.0.0";

        // Уведоаление для всех секторов
        if ($this->callcenter and $this->sector)
            $channels[] = "App.Admin.AuthQueries.{$this->callcenter}.0";

        $response = [];

        foreach (array_unique($channels) as $channel) {
            $response[] = new PrivateChannel($channel);
        }

        return $response;
    }
}
