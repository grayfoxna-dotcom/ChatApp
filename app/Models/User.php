<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'avatar',
        'password',
        'isActive',
        'email_verified_at',
        'last_seen_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Thuộc tính ảo sẽ tự động đính kèm khi serialize JSON
     */
    protected $appends = ['is_online', 'time_ago'];

    public function getIsOnlineAttribute()
    {
        if (!$this->last_seen_at) return false;
        return now()->diffInMinutes($this->last_seen_at) < 2;
    }

    public function getTimeAgoAttribute()
    {
        if (!$this->last_seen_at) return "Ngoại tuyến";

        $minutes = now()->diffInMinutes($this->last_seen_at);
        
        if ($minutes < 2) {
            return "Đang hoạt động";
        } elseif ($minutes < 60) {
            return "Hoạt động {$minutes} phút trước";
        } 
        
        $hours = now()->diffInHours($this->last_seen_at);
        if ($hours < 24) {
            return "Hoạt động {$hours} giờ trước";
        }
        
        $days = now()->diffInDays($this->last_seen_at);
        if ($days < 7) {
            return "Hoạt động {$days} ngày trước";
        }
        
        return "Ngoại tuyến";
    }

    /**
     * Danh sách các cuộc hội thoại người dùng tham gia
     */
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user')
            ->withPivot('invite_status', 'last_delivered_id', 'last_read_id', 'cleared_at');
    }

    /**
     * Lời mời kết bạn đã gửi
     */
    public function sentRelationships()
    {
        return $this->hasMany(Relationship::class, 'requester_id');
    }

    /**
     * Lấy danh sách bạn bè (Đã chấp nhận kết bạn)
     * Trả về Query Builder của Model User
     */
    public function friends()
    {
        $friendIds = Relationship::where(function($q) {
                $q->where('requester_id', $this->id)
                  ->where('status', 'accepted');
            })
            ->orWhere(function($q) {
                $q->where('addressee_id', $this->id)
                  ->where('status', 'accepted');
            })
            ->get()
            ->map(function($rel) {
                return $rel->requester_id === $this->id ? $rel->addressee_id : $rel->requester_id;
            });

        return User::whereIn('id', $friendIds);
    }
}
