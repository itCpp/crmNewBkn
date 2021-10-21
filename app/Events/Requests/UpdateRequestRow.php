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

class UpdateRequestRow implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Данные о заявке
     *
     * @var object
     */
    protected $row;

    /**
     * Идентификатор коллцентра
     * 
     * @var null|int
     */
    protected $callcenter = null;

    /**
     * Персональный идентификационный номер сотрудника, который должен проигнорировать уведомление
     * 
     * @var array
     */
    protected $toExclude;

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

        if ($callcenter = CallcenterSector::find($this->row->callcenter_sector))
            $this->callcenter = $callcenter->id;
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
        $channels[] = "App.Requests.All.0.0";

        if ($this->callcenter) {
            $channels[] = "App.Requests.All.{$this->callcenter}.{$this->row->callcenter_sector}";
            $channels[] = "App.Requests.All.{$this->callcenter}.0";
        }

        if ($this->row->pin)
            $channels[] = "App.Requests.{$this->row->pin}";

        return collect(array_unique($channels ?? []))->map(function ($channel) {
            return new PrivateChannel($channel);
        })->toArray();
    }
}
