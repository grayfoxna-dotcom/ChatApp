<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements \Illuminate\Contracts\Broadcasting\ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation_id;
    public $user_id;
    public $message_id;

    /**
     * Create a new event instance.
     */
    public $user_avatar;

    public function __construct($conversation_id, $user_id, $message_id)
    {
        $this->conversation_id = $conversation_id;
        $this->user_id = $user_id;
        $this->message_id = $message_id;

        $user = \App\Models\User::find($user_id);
        $this->user_avatar = $user ? $user->avatar : null;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->conversation_id),
        ];
    }

    public function broadcastAs()
    {
        return 'MessageReadEvent';
    }
}
