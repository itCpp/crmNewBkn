<?php

namespace App\Events;

use App\Http\Controllers\Sms\Sms;
use App\Models\SmsMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewSmsRequestsEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\SmsMessage|array $row
     * @return void
     */
    public function __construct(
        public SmsMessage|array $row
    ) {
        //
    }

    /**
     * Get broadcast data.
     *
     * @return array
     */
    public function broadcastWith()
    {
        if (is_array($this->row)) {
            return [
                'rows' => $this->row,
            ];
        }

        return [
            'row' => (new Sms)->getRowSms($this->row)
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('App.Crm.Sms.Requests');
    }
}
