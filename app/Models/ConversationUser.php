<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationUser extends Model
{
    protected $table = 'conversation_user';

    protected $fillable = [
        'user_id',
        'conversation_id'
    ];

    public $timestamps = false; // Bảng này không có timestamps trong migration

    use HasFactory;
}
