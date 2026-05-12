<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageDeletion extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'user_id'
    ];

    /**
     * Thuộc về tin nhắn nào
     */
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Thuộc về người dùng nào
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
