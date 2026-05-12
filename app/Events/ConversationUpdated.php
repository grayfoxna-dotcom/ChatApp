<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversationId;
    public $userId;

    /**
     * Sự kiện thông báo cập nhật hội thoại cho từng người dùng cụ thể
     */
    public function __construct($conversationId, $userId)
    {
        $this->conversationId = $conversationId;
        $this->userId = $userId;
    }

    /**
     * Phát sóng trên kênh riêng tư của từng người dùng (user.{id})
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('user.' . $this->userId);
    }

    /**
     * Tên sự kiện để Client lắng nghe
     */
    public function broadcastAs(): string
    {
        return 'ConversationUpdatedEvent';
    }

    /**
     * Dữ liệu gửi về cho Client
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
        ];
    }
}
