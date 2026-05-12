<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageUnsent implements \Illuminate\Contracts\Broadcasting\ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageId;
    public $conversationId;
    public $deletedAt;
    public $senderId;
    public $senderName;

    /**
     * Khởi tạo Event khi tin nhắn bị thu hồi
     */
    public function __construct(Message $message)
    {
        $this->messageId = $message->id;
        $this->conversationId = $message->conversation_id;
        $this->deletedAt = $message->deleted_at->toIso8601String();
        $this->senderId = $message->sender_id;
        $this->senderName = $message->sender ? $message->sender->name : null;
    }

    /**
     * Phát sóng trên kênh cuộc hội thoại
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('conversation.' . $this->conversationId);
    }

    /**
     * Tên sự kiện ở Client
     */
    public function broadcastAs(): string
    {
        return 'MessageUnsentEvent';
    }

    /**
     * Dữ liệu gửi về Client
     */
    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
            'conversation_id' => $this->conversationId,
            'deleted_at' => $this->deletedAt,
            'sender_id' => $this->senderId,
            'sender_name' => $this->senderName,
        ];
    }
}
