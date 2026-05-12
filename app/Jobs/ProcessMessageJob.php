<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $conversationId;
    public $receiverId;
    public $senderId;
    public $contentData;
    public $determinedType;
    public $tempId;
    public $replyToId;

    public function __construct($conversationId, $receiverId, $senderId, $contentData, $determinedType, $tempId = null, $replyToId = null)
    {
        $this->conversationId = $conversationId;
        $this->receiverId = $receiverId;
        $this->senderId = $senderId;
        $this->contentData = $contentData;
        $this->determinedType = $determinedType;
        $this->tempId = $tempId;
        $this->replyToId = $replyToId;
    }

    public function handle(): void
    {
        try {
            DB::transaction(function () {
                $conversation = null;

                // 1. Xác định hoặc tạo cuộc hội thoại
                if ($this->conversationId) {
                    $conversation = \App\Models\Conversation::find($this->conversationId);
                    if (!$conversation) {
                        $this->failWithNotification("Cuộc hội thoại không tồn tại.");
                        return;
                    }

                    if (!$conversation->users()->where('user_id', $this->senderId)->exists()) {
                        $this->failWithNotification("Bạn không thuộc cuộc hội thoại này.");
                        return;
                    }
                } elseif ($this->receiverId) {
                    $conversation = \App\Models\Conversation::where('is_group', false)
                        ->whereHas('users', function ($q) { $q->where('user_id', $this->senderId); })
                        ->whereHas('users', function ($q) { $q->where('user_id', $this->receiverId); })
                        ->first();

                    if (!$conversation) {
                        $conversation = \App\Models\Conversation::create([
                            'is_group' => false,
                            'last_update_at' => now(),
                        ]);
                        $conversation->users()->attach([$this->senderId, $this->receiverId], ['invite_status' => '1']);
                    }
                } else {
                    $this->failWithNotification("Thiếu thông tin người nhận.");
                    return;
                }

                // 2. Xử lý di chuyển file từ pending sang thư mục chính thức
                $this->moveFilesToOfficialFolders();

                // 3. Tạo tin nhắn mới
                $message = \App\Models\Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $this->senderId,
                    'content' => $this->contentData,
                    'type' => $this->determinedType,
                    'reply_to_id' => $this->replyToId,
                ]);

                // 4. Cập nhật thời gian hoạt động cuối của hội thoại
                $conversation->update(['last_update_at' => now()]);

                // 5. Phát sự kiện realtime tin nhắn
                \App\Events\MessageSent::dispatch($message, $this->tempId);

                // 6. Phát sự kiện cập nhật hội thoại cho từng người dùng trong nhóm
                // Việc này giúp hiện lại hội thoại nếu người dùng đã xóa (ẩn) trước đó
                $participantIds = $conversation->users()->pluck('users.id');
                foreach ($participantIds as $pId) {
                    \App\Events\ConversationUpdated::dispatch($conversation->id, $pId);
                }
            });
        } catch (Exception $e) {
            \Log::error("Lỗi ProcessMessageJob: " . $e->getMessage());
            // Laravel sẽ tự động thử lại (retry) Job này nếu có lỗi ngoại lệ
            throw $e;
        }
    }

    /**
     * Xử lý khi Job thất bại logic (không phải lỗi hệ thống)
     */
    private function failWithNotification($message)
    {
        $this->cleanupFiles();
        \App\Events\MessageFailed::dispatch($this->tempId, $this->senderId, $message);
    }

    private function moveFilesToOfficialFolders()
    {
        if (!is_array($this->contentData)) return;

        $fields = ['file', 'files'];
        foreach ($fields as $field) {
            if (isset($this->contentData[$field])) {
                if ($field === 'file') {
                    $this->contentData['file'] = $this->moveSingleFile($this->contentData['file']);
                } else {
                    $newPaths = [];
                    foreach ($this->contentData['files'] as $path) {
                        $newPaths[] = $this->moveSingleFile($path);
                    }
                    $this->contentData['files'] = $newPaths;
                }
            }
        }
    }

    private function moveSingleFile($oldPath)
    {
        if (!str_contains($oldPath, '/uploads/pending/')) return $oldPath;

        $filename = basename($oldPath);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $subFolder = match($extension) {
            'mp4', 'mov', 'avi' => 'videos',
            'mp3', 'wav', 'm4a', 'aac', 'caf' => 'voice',
            'jpeg', 'png', 'jpg', 'gif', 'webp' => 'images',
            default => 'files',
        };

        $newRelativePath = "/uploads/{$subFolder}/{$filename}";
        $fullOldPath = public_path($oldPath);
        $fullNewPath = public_path($newRelativePath);

        $targetDir = dirname($fullNewPath);
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        if (file_exists($fullOldPath)) {
            rename($fullOldPath, $fullNewPath);
        }

        return $newRelativePath;
    }

    private function cleanupFiles()
    {
        if (!is_array($this->contentData)) return;

        $paths = [];
        if (isset($this->contentData['file'])) $paths[] = $this->contentData['file'];
        if (isset($this->contentData['files'])) $paths = array_merge($paths, $this->contentData['files']);

        foreach ($paths as $path) {
            $fullPath = public_path($path);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }
}
