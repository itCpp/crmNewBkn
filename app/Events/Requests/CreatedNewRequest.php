<?php

namespace App\Events\Requests;

use App\Models\CallcenterSector;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Отправка уведомлений при появлении новой заявки
 */
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
     * Результат обработки входящего запроса
     * @see \App\Http\Controllers\Requests\AddRequest::$response
     * 
     * @var array
     */
    public $result;

    /**
     * Create a new event instance.
     *
     * @param object $row Данные по заявке
     * @param array $result Результат обработки входящего запроса
     * @return void
     */
    public function __construct($row, $result)
    {
        $this->row = $row;
        $this->result = $result;
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
            'result' => $this->result,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // Доступ ко всем секторам и ко всем коллцентрам
        $channels[] = new PrivateChannel('App.Requests.All.0.0');

        if ($sector = CallcenterSector::find($this->row->callcenter_sector ?? null)) {
            $channels[] = new PrivateChannel("App.Requests.All.{$sector->callcenter_id}.0");
            $channels[] = new PrivateChannel("App.Requests.All.{$sector->callcenter_id}.{$sector->id}");
        }

        return $channels;
    }
}
