<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'avatar',
        'last_update_at',
        'is_group',
        'invite_id',
    ];

    protected $casts = [
        'is_group' => 'boolean',
        'last_update_at' => 'datetime'
    ];

    /**
     * Danh sách người dùng trong cuộc hội thoại
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_user')
            ->withPivot('invite_status', 'last_delivered_id', 'last_read_id', 'cleared_at');
    }

    /**
     * Danh sách tin nhắn trong cuộc hội thoại
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Tin nhắn mới nhất
     */
    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Người gửi lời mời
     */
    public function inviter()
    {
        return $this->belongsTo(User::class, 'invite_id');
    }
}
