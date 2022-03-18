<?php

namespace App\Events\Users;

use App\Http\Controllers\Users\Worktime;
use App\Models\UserWorkTime;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChangeUserWorkTime implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Даннные об установленном рабочем времени
     * 
     * @var array
     */
    public $worktime;

    /**
     * Идентификатор сотрудника
     * 
     * @var int
     */
    protected $userId;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\UserWorkTime $worktime
     * @param int $id
     * @return void
     */
    public function __construct($worktime, $id)
    {
        $this->worktime = Worktime::getDataForEvent($worktime);
        $this->userId = $id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return [
            new PrivateChannel("App.User.{$this->userId}"),
            new PrivateChannel("App.User.Page.{$this->userId}"),
        ];
    }
}
