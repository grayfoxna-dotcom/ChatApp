<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'content',
        'sender_id',
        'type',
        'reply_to_id',
        'temp_id',
        'deleted_at'
    ];

    protected $casts = [
        'content' => 'json',
        'deleted_at' => 'datetime',
    ];

    /**
     * Thuộc về cuộc hội thoại nào
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Người gửi tin nhắn
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Tin nhắn được trích dẫn (Reply)
     */
    public function replyTo()
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }

    /**
     * Danh sách các yêu cầu xóa mềm từ người dùng
     */
    public function deletions()
    {
        return $this->hasMany(MessageDeletion::class);
    }
}
