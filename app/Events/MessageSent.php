<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements \Illuminate\Contracts\Broadcasting\ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $message;
    public $tempId;

    /**
     * Khởi tạo Event nhận vào đối tượng Message mới gửi
     */
    public function __construct(Message $message, $tempId = null)
    {
        $this->message = $message;
        $this->tempId = $tempId;
    }

    /**
     * Kênh phát sóng riêng tư bảo mật cho cuộc trò chuyện cụ thể
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('conversation.' . $this->message->conversation_id);
    }

    /**
     * Đặt tên sự kiện tùy biến ở Client
     */
    public function broadcastAs(): string
    {
        return 'MessageSentEvent';
    }

    /**
     * Đóng gói dữ liệu tin nhắn và người gửi gửi về Flutter Client
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'temp_id' => $this->tempId,
                'conversation_id' => $this->message->conversation_id,
                'content' => $this->message->content,
                'sender_id' => $this->message->sender_id,
                'type' => $this->message->type,
                'reply_to_id' => $this->message->reply_to_id,
                'reply_to' => $this->message->replyTo ? [
                    'id' => $this->message->replyTo->id,
                    'content' => $this->message->replyTo->content,
                    'sender_id' => $this->message->replyTo->sender_id,
                    'sender_name' => $this->message->replyTo->sender ? $this->message->replyTo->sender->name : null,
                    'type' => $this->message->replyTo->type,
                    'created_at' => $this->message->replyTo->created_at->toIso8601String(),
                ] : null,
                'created_at' => $this->message->created_at->toIso8601String(),
            ],
            'sender' => [
                'id' => $this->message->sender->id,
                'name' => $this->message->sender->name,
                'avatar' => $this->message->sender->avatar,
            ]
        ];
    }}
