<?php

namespace App\Events;

use App\Http\Controllers\Controller;
use App\Models\IncomingCall;
use App\Models\IncomingCallsToSource;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncomingCalls implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Данные о входящем звонке
     * 
     * @var object
     */
    public $data;

    /**
     * Флаг необходимости сменить наименования события
     * 
     * @var null|string
     */
    public $update;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\IncomingCall $data
     * @param bool $update
     * @return void
     */
    public function __construct(IncomingCall $data, $update = false)
    {
        $this->data = $data;
        $this->update = $update;
    }

    /**
     * Данные для трансляции
     *
     * @return array
     */
    public function broadcastWith()
    {
        $data = $this->data->toArray();
        
        $data['phone'] = Controller::decrypt($data['phone']);

        if ($phone = Controller::checkPhone($data['phone'], 2))
            $data['phone'] = $phone;

        $data['source'] = IncomingCallsToSource::where('extension', $data['sip'])->first();

        return [
            'data' => $data,
            'update' => $this->update,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('App.Admin.Calls');
    }
}
