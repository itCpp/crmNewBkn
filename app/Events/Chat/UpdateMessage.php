<?php

namespace App\Events\Chat;

use App\Models\ChatRoomsUser;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public array $message,
    ) {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        $chats = ChatRoomsUser::where('chat_id', $this->message['chat_id'])
            ->get()
            ->map(function ($row) {
                return $row->user_id;
            });

        foreach ($chats as $channel) {
            $channels[] = new PrivateChannel("Chat.Room.{$channel}");
        }

        return $channels ?? [];
    }
}
