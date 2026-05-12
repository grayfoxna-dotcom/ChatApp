<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageFailed implements \Illuminate\Contracts\Broadcasting\ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tempId;
    public $userId;
    public $errorMessage;

    /**
     * Create a new event instance.
     */
    public function __construct($tempId, $userId, $errorMessage = "Tin nhắn không thể gửi do lỗi hệ thống.")
    {
        $this->tempId = $tempId;
        $this->userId = $userId;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->userId),
        ];
    }

    public function broadcastAs()
    {
        return 'MessageFailedEvent';
    }

    public function broadcastWith()
    {
        return [
            'temp_id' => $this->tempId,
            'error_message' => $this->errorMessage,
        ];
    }
}
