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

class UpdateRequestRowForSector implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Данные обновляемой заявки
     * 
     * @var object
     */
    protected $row;

    /**
     * Идентификатор старого коллцентра
     * 
     * @var null|int
     */
    protected $oldCallcenter = null;

    /**
     * Идентификатор нового коллцентра
     * 
     * @var null|int
     */
    protected $newCallcenter = null;

    /**
     * Идентификтаор старого сектора
     * 
     * @var null|int
     */
    protected $oldSector;

    /**
     * Идентификтаор нового сектора
     * 
     * @var null|int
     */
    protected $newSector;

    /**
     * Create a new event instance.
     *
     * @param  object  $row
     * @param  int  $sector Идентификатор сектора
     * @param  bool  $drop Флаг удаления заявки
     * @return void
     */
    public function __construct($row)
    {
        $this->row = $row;

        $this->oldSector = $row->oldSector ?? null;
        $this->newSector = $row->newSector ?? null;

        $this->getIdCallCenters();
    }

    /**
     * Определение идентификтаоров коллцентров
     * 
     * @return null
     */
    public function getIdCallCenters()
    {
        if ($this->oldSector and $old = CallcenterSector::find($this->oldSector))
            $this->oldCallcenter = $old->id;

        if ($this->newSector and $new = CallcenterSector::find($this->newSector))
            $this->newCallcenter = $new->id;

        return null;
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
            'dropCallCenter' => $this->oldCallcenter,
            'dropSector' => $this->oldSector,
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

        if ($this->oldCallcenter and $this->oldSector) {
            $channels[] = "App.Requests.All.{$this->oldCallcenter}.{$this->oldSector}";
            $channels[] = "App.Requests.All.{$this->oldCallcenter}.0";
        }

        if ($this->newCallcenter and $this->newSector) {
            $channels[] = "App.Requests.All.{$this->newCallcenter}.{$this->newSector}";
            $channels[] = "App.Requests.All.{$this->newCallcenter}.0";
        }

        return collect(array_unique($channels ?? []))->map(function ($channel) {
            return new PrivateChannel($channel);
        })->toArray();
    }
}
