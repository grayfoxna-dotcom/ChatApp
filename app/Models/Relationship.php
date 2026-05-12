<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Relationship extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'addressee_id',
        'status', // pending, accepted, declined, blocked
    ];

    /**
     * Lấy người dùng gửi lời mời kết bạn
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Lấy người dùng nhận lời mời kết bạn
     */
    public function addressee()
    {
        return $this->belongsTo(User::class, 'addressee_id');
    }
}
