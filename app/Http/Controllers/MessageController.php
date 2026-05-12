<?php

namespace App\Http\Controllers;

use App\Events\MessageDelivered;
use App\Events\MessageRead;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\Relationship;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * 1. Lấy danh sách các cuộc trò chuyện của người dùng hiện tại
     * Route: GET /api/getConversations
     */
    public function getConversations(Request $request)
    {
        try {
            $user = auth()->user();

            // Lấy các cuộc trò chuyện mà người dùng đã chấp nhận tham gia (invite_status = 1)
            $conversations = $user->conversations()
                ->wherePivot('invite_status', '1')
                ->with(['users', 'lastMessage.sender'])
                ->get();

            $formatted = $conversations->map(function ($conversation) use ($user) {
                $partner = null;
                $name = $conversation->name;
                $avatar = $conversation->avatar;

                if (!$conversation->is_group) {
                    $partner = $conversation->users->first(function ($u) use ($user) {
                        return $u->id !== $user->id;
                    });

                    if ($partner) {
                        $name = $partner->name;
                        $avatar = $partner->avatar;
                    }
                }

                $myUser = $conversation->users->firstWhere('id', $user->id);
                $myPivot = $myUser ? $myUser->pivot : null;

                // Lấy tin nhắn cuối cùng mà người dùng này chưa xóa (Xóa phía tôi)
                $realLastMessage = $conversation->messages()
                    ->where(function($q) use ($myPivot) {
                        if ($myPivot && $myPivot->cleared_at) {
                            $q->where('created_at', '>', $myPivot->cleared_at);
                        }
                    })
                    ->whereDoesntHave('deletions', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })
                    ->with('sender')
                    ->latest()
                    ->first();

                return [
                    'id' => $conversation->id,
                    'name' => $name,
                    'avatar' => $avatar,
                    'is_group' => $conversation->is_group,
                    'last_update_at' => $conversation->last_update_at ? $conversation->last_update_at->toIso8601String() : null,
                    'last_read_id' => $myPivot ? $myPivot->last_read_id : null,
                    'last_delivered_id' => $myPivot ? $myPivot->last_delivered_id : null,
                    'unread_count' => $myPivot ? $conversation->messages()
                        ->where(function($q) use ($user, $myPivot) {
                            // Bọc điều kiện người gửi trong một closure để tránh lỗi grouping với cleared_at
                            $q->where(function($sub) use ($user) {
                                $sub->where('sender_id', '!=', $user->id)
                                   ->orWhereNull('sender_id');
                            });
                            
                            if ($myPivot->cleared_at) {
                                $q->where('created_at', '>', $myPivot->cleared_at);
                            }
                        })
                        ->where('id', '>', $myPivot->last_read_id ?? 0)
                        ->whereDoesntHave('deletions', function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                        })
                        ->count() : 0,
                    'partner' => $partner ? [
                        'id' => $partner->id,
                        'name' => $partner->name,
                        'avatar' => $partner->avatar,
                        'is_online' => $partner->is_online,
                        'time_ago' => $partner->time_ago,
                        'last_read_id' => $partner->pivot->last_read_id,
                        'blocked_by_me' => Relationship::where('requester_id', $user->id)
                            ->where('addressee_id', $partner->id)
                            ->where('status', 'blocked')
                            ->exists(),
                        'blocked_me' => Relationship::where('requester_id', $partner->id)
                            ->where('addressee_id', $user->id)
                            ->where('status', 'blocked')
                            ->exists(),
                    ] : null,

                    'last_message' => $realLastMessage ? [
                        'id' => $realLastMessage->id,
                        'content' => $realLastMessage->content,
                        'sender_id' => $realLastMessage->sender_id,
                        'sender_name' => $realLastMessage->sender ? $realLastMessage->sender->name : null,
                        'type' => $realLastMessage->type,
                        'deleted_at' => $realLastMessage->deleted_at ? $realLastMessage->deleted_at->toIso8601String() : null,
                        'created_at' => $realLastMessage->created_at->toIso8601String(),
                    ] : null,
                ];
            })->filter(function($conv) {
                // Chỉ hiện cuộc trò chuyện nếu:
                // 1. Có ít nhất 1 tin nhắn chưa xóa (real_last_message != null)
                // 2. Hoặc là cuộc hội thoại mới tinh (chưa từng xóa - cleared_at == null)
                // Điều này giúp ẩn hội thoại đã xóa sạch lịch sử giống Messenger.
                return $conv['last_message'] != null || $conv['last_read_id'] === null;
            })->sortByDesc('last_update_at')->values();

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Lấy danh sách hội thoại thành công',
                'data' => $formatted
            ]);
        } catch (\Exception $e) {
            \Log::error("Lỗi getConversations: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Không thể tải danh sách hội thoại: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 2. Lấy danh sách tin nhắn trong một cuộc trò chuyện
     * Route: GET /api/getMessages
     */
    public function getMessages(Request $request)
    {
        try {
            $user = auth()->user();
            $conversationId = $request->input('conversation_id');
            $beforeId = $request->input('before_id');
            $afterId = $request->input('after_id');
            $aroundId = $request->input('around_id');
            $limit = $request->input('limit', 30);

            if (!$conversationId) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => 'Thiếu ID cuộc hội thoại'
                ]);
            }

            $conversation = $user->conversations()->where('conversation_id', $conversationId)->first();
            if (!$conversation) {
                return response()->json([
                    'status_response' => 'error', 
                    'message_response' => 'Cuộc trò chuyện không tồn tại hoặc bạn không có quyền truy cập'
                ]);
            }

            $myPivot = $conversation->users()->where('user_id', $user->id)->first()->pivot;

            // Xây dựng Query cơ bản
            $baseQuery = $conversation->messages()
                ->where(function($q) use ($myPivot) {
                    if ($myPivot->cleared_at) {
                        $q->where('created_at', '>', $myPivot->cleared_at);
                    }
                })
                ->whereDoesntHave('deletions', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->with(['sender', 'replyTo.sender']);

            if ($aroundId) {
                // TRƯỜNG HỢP 1: Lấy tin nhắn xung quanh một ID (Anchor)
                // Hỗ trợ lấy lệch (ví dụ: 10 tin trên, 3 tin dưới)
                $beforeLimit = $request->input('before_limit');
                $afterLimit = $request->input('after_limit');

                if ($beforeLimit !== null && $afterLimit !== null) {
                    $olderLimit = intval($beforeLimit);
                    $newerLimit = intval($afterLimit);
                } else {
                    $halfLimit = floor($limit / 2);
                    $olderLimit = $halfLimit;
                    $newerLimit = $halfLimit;
                }
                
                $olderMessages = (clone $baseQuery)
                    ->where('id', '<=', $aroundId)
                    ->latest('id')
                    ->take($olderLimit + 1) // Lấy tin mục tiêu + các tin cũ hơn
                    ->get();

                $newerMessages = (clone $baseQuery)
                    ->where('id', '>', $aroundId)
                    ->oldest('id')
                    ->take($newerLimit) // Lấy các tin mới hơn
                    ->get();

                $messages = $olderMessages->merge($newerMessages)->sortBy('id')->values();
            } elseif ($afterId) {
                // TRƯỜNG HỢP 2: Lấy tin nhắn mới hơn (Cuộn xuống)
                $messages = $baseQuery->where('id', '>', $afterId)
                    ->oldest('id')
                    ->take($limit)
                    ->get()
                    ->sortBy('id')
                    ->values();
            } else {
                // TRƯỜNG HỢP 3: Lấy tin nhắn cũ hơn (Mặc định hoặc Cuộn lên)
                $query = $baseQuery->latest('id');
                if ($beforeId) {
                    $query->where('id', '<', $beforeId);
                }
                $messages = $query->take($limit)->get()->sortBy('id')->values();
            }

            $formatted = $messages->map(function ($message) {
                $replyData = null;
                if ($message->replyTo) {
                    $replyData = [
                        'id' => $message->replyTo->id,
                        'content' => $message->replyTo->content,
                        'sender_id' => $message->replyTo->sender_id,
                        'sender_name' => $message->replyTo->sender ? $message->replyTo->sender->name : null,
                        'type' => $message->replyTo->type,
                        'created_at' => $message->replyTo->created_at->toIso8601String(),
                    ];
                }

                return [
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'content' => $message->content,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender ? $message->sender->name : null,
                    'sender_avatar' => $message->sender ? $message->sender->avatar : null,
                    'type' => $message->type,
                    'reply_to_id' => $message->reply_to_id,
                    'reply_to' => $replyData,
                    'deleted_at' => $message->deleted_at ? $message->deleted_at->toIso8601String() : null,
                    'created_at' => $message->created_at->toIso8601String(),
                ];
            });

            // Lấy danh sách Read Receipts
            $readReceipts = $conversation->users()
                ->where('users.id', '!=', $user->id)
                ->wherePivot('invite_status', '1')
                ->get(['users.id as user_id', 'users.name', 'users.avatar', 'last_read_id', 'last_delivered_id']);

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Lấy tin nhắn thành công',
                'data' => [
                    'messages' => $formatted,
                    'read_receipts' => $readReceipts
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error("Lỗi getMessages: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Lỗi khi tải tin nhắn: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 2.5. Đồng bộ các tin nhắn thay đổi trong lúc offline (Incremental Sync)
     * Route: GET /api/syncMessages
     * Nhận: last_sync_at (ISO8601) - Mốc thời gian đồng bộ cuối cùng thành công
     * Trả về: Danh sách tin nhắn có updated_at > last_sync_at trong tất cả hội thoại của user
     */
    public function syncMessages(Request $request)
    {
        try {
            $user = auth()->user();
            $lastSyncAt = $request->input('last_sync_at');

            if (!$lastSyncAt) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => 'Thiếu mốc thời gian đồng bộ'
                ]);
            }

            // Lấy tất cả cuộc hội thoại user đang tham gia
            $conversationIds = $user->conversations()->pluck('conversations.id');

            // Lấy tất cả tin nhắn có updated_at > last_sync_at trong các hội thoại đó
            // Bao gồm cả tin bị thu hồi (deleted_at != null) để app có thể cập nhật trạng thái
            $changedMessages = \App\Models\Message::whereIn('conversation_id', $conversationIds)
                ->where('updated_at', '>', $lastSyncAt)
                ->whereDoesntHave('deletions', function ($query) use ($user) {
                    // Không lấy tin nhắn mà user đã tự xóa ở phía mình
                    $query->where('user_id', $user->id);
                })
                ->with(['sender', 'replyTo.sender'])
                ->orderBy('id', 'asc') // Sắp xếp tăng dần để Client xử lý theo thứ tự thời gian
                ->get();

            $formatted = $changedMessages->map(function ($message) {
                $replyData = null;
                if ($message->replyTo) {
                    $replyData = [
                        'id' => $message->replyTo->id,
                        'content' => $message->replyTo->content,
                        'sender_id' => $message->replyTo->sender_id,
                        'sender_name' => $message->replyTo->sender ? $message->replyTo->sender->name : null,
                        'type' => $message->replyTo->type,
                        'created_at' => $message->replyTo->created_at->toIso8601String(),
                    ];
                }

                return [
                    'id'             => $message->id,
                    'conversation_id'=> $message->conversation_id,
                    'content'        => $message->content,
                    'sender_id'      => $message->sender_id,
                    'sender_name'    => $message->sender ? $message->sender->name : null,
                    'sender_avatar'  => $message->sender ? $message->sender->avatar : null,
                    'type'           => $message->type,
                    'reply_to_id'    => $message->reply_to_id,
                    'reply_to'       => $replyData,
                    'deleted_at'     => $message->deleted_at ? $message->deleted_at->toIso8601String() : null,
                    'created_at'     => $message->created_at->toIso8601String(),
                    'updated_at'     => $message->updated_at->toIso8601String(),
                ];
            });

            return response()->json([
                'status_response'  => 'success',
                'message_response' => 'Đồng bộ thành công',
                'data' => [
                    'changed_messages' => $formatted,
                    'server_time'      => now()->toIso8601String(), // Trả về mốc thời gian Server hiện tại để Client làm checkpoint mới
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error("Lỗi syncMessages: " . $e->getMessage());
            return response()->json([
                'status_response'  => 'error',
                'message_response' => 'Lỗi khi đồng bộ tin nhắn: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 3. Gửi tin nhắn mới (Xử lý qua Job để tối ưu tốc độ)
     * Route: POST /api/sendMessage
     */
    public function sendMessage(Request $request)
    {
        try {
            $user = auth()->user();
            $conversationId = $request->input('conversation_id');
            $content = $request->input('content');
            $type = $request->input('type', 'text');
            $tempId = $request->input('temp_id');

            $contentData = $content;

            // Xử lý upload file nếu có
            if ($request->hasFile('file') || $request->hasFile('files')) {
                $files = $request->hasFile('file') ? [$request->file('file')] : $request->file('files');
                $paths = [];
                
                foreach ($files as $file) {
                    $extension = strtolower($file->getClientOriginalExtension());
                    $size = $file->getSize();

                    $currentFileType = '';
                    if (in_array($extension, ['mp4', 'mov', 'avi'])) {
                        if ($size > 100 * 1024 * 1024) throw new \Exception("Video quá lớn (Max 100MB)");
                        $currentFileType = 'video';
                    } elseif (in_array($extension, ['m4a', 'mp3', 'wav', 'aac', 'caf'])) {
                        if ($size > 10 * 1024 * 1024) throw new \Exception("File ghi âm quá lớn (Max 10MB)");
                        $currentFileType = 'voice';
                    } elseif (in_array($extension, ['jpeg', 'png', 'jpg', 'gif', 'webp'])) {
                        if ($size > 10 * 1024 * 1024) throw new \Exception("Ảnh quá lớn (Max 10MB)");
                        $currentFileType = 'image';
                    } else {
                        // Whitelist các loại tệp tin được phép
                        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', '7z', 'psd', 'ai'];
                        if (!in_array($extension, $allowedExtensions)) throw new \Exception("Định dạng .{$extension} không được hỗ trợ.");
                        if ($size > 150 * 1024 * 1024) throw new \Exception("Tệp tin quá lớn (Max 150MB)");
                        $currentFileType = 'file';
                    }

                    // Xác định type tổng thể cho tin nhắn
                    if ($type === null) {
                        $type = $currentFileType;
                    } elseif ($type !== $currentFileType) {
                        if (($type === 'image' && $currentFileType === 'video') || ($type === 'video' && $currentFileType === 'image') || $type === 'image/video') {
                            $type = 'image/video';
                        }
                    }

                    $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                    $file->move(public_path('uploads/pending'), $filename);
                    $paths[] = "/uploads/pending/" . $filename;
                }

                $contentData = (count($paths) === 1 && $request->hasFile('file')) 
                    ? ['file' => $paths[0]] 
                    : ['files' => $paths];

                if ($request->input('content')) {
                    $contentData['text'] = $request->input('content');
                }

                if ($request->input('size')) {
                    $contentData['size'] = $request->input('size');
                }
            }

            $replyToId = $request->input('reply_to_id');

            if ($replyToId) {
                $repliedMessage = Message::find($replyToId);
                if (!$repliedMessage || $repliedMessage->deleted_at != null) {
                    return response()->json([
                        'status_response' => 'error',
                        'message_response' => 'Không thể trả lời tin nhắn đã bị gỡ'
                    ]);
                }
            }

            // Dispatch Job xử lý lưu vào DB và gửi Socket
            \App\Jobs\ProcessMessageJob::dispatch($conversationId, null, $user->id, $contentData, $type, $tempId, $replyToId);

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Tin nhắn đang được xử lý',
                'temp_id' => $tempId
            ]);
        } catch (\Exception $e) {
            \Log::error("Lỗi sendMessage: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Không thể gửi tin nhắn: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 4. Đánh dấu tin nhắn là đã nhận (Delivered)
     * Route: POST /api/markDelivered
     */
    public function markDelivered(Request $request)
    {
        try {
            $user = auth()->user();
            $conversationId = $request->input('conversation_id');
            $messageId = $request->input('message_id');

            if (!$conversationId || !$messageId) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => 'Thiếu dữ liệu cập nhật trạng thái nhận'
                ]);
            }

            DB::transaction(function () use ($user, $conversationId, $messageId) {
                $conversation = $user->conversations()
                    ->where('conversation_id', $conversationId)
                    ->lockForUpdate()
                    ->first();

                if ($conversation) {
                    $pivot = $conversation->pivot;
                    if ($pivot->last_delivered_id < $messageId || $pivot->last_delivered_id == null) {
                        $user->conversations()->updateExistingPivot($conversationId, [
                            'last_delivered_id' => $messageId
                        ]);
                        
                        broadcast(new MessageDelivered($conversationId, $user->id, $messageId))->toOthers();
                    }
                }
            });

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Cập nhật trạng thái nhận thành công'
            ]);
        } catch (\Exception $e) {
            \Log::error("Lỗi markDelivered: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Lỗi cập nhật trạng thái nhận: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 5. Đánh dấu tin nhắn là đã xem (Read)
     * Route: POST /api/markRead
     */
    public function markRead(Request $request)
    {
        try {
            $user = auth()->user();
            $conversationId = $request->input('conversation_id');
            $messageId = $request->input('message_id');

            if (!$conversationId || !$messageId) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => 'Thiếu dữ liệu cập nhật trạng thái xem'
                ]);
            }

            DB::transaction(function () use ($user, $conversationId, $messageId) {
                $conversation = $user->conversations()
                    ->where('conversation_id', $conversationId)
                    ->lockForUpdate()
                    ->first();

                if ($conversation) {
                    $pivot = $conversation->pivot;
                    if ($pivot->last_read_id < $messageId || $pivot->last_read_id == null) {
                        $user->conversations()->updateExistingPivot($conversationId, [
                            'last_read_id' => $messageId,
                            'last_delivered_id' => $messageId
                        ]);
                        
                        broadcast(new MessageRead($conversationId, $user->id, $messageId))->toOthers();
                    }
                }
            });

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Cập nhật trạng thái xem thành công'
            ]);
        } catch (\Exception $e) {
            \Log::error("Lỗi markRead: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Lỗi cập nhật trạng thái xem: ' . $e->getMessage()
            ]);
        }
    }
    /**
     * 6. Chuyển tiếp tin nhắn sang cuộc hội thoại khác
     * Route: POST /api/forwardMessage
     */
    public function forwardMessage(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Hỗ trợ cả 1 message_id (đơn lẻ) hoặc message_ids (mảng)
            $originalMessageIds = $request->input('message_ids');
            if (!$originalMessageIds) {
                $singleId = $request->input('message_id');
                $originalMessageIds = $singleId ? [$singleId] : [];
            }
            
            $targetConversationId = $request->input('conversation_id');
            $targetUserId = $request->input('receiver_id');
            // Nhận mảng temp_ids tương ứng với message_ids từ client (nếu có)
            $tempIds = $request->input('temp_ids', []);

            if (empty($originalMessageIds) || (!$targetConversationId && !$targetUserId)) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => 'Thiếu thông tin tin nhắn hoặc người nhận'
                ]);
            }

            return DB::transaction(function () use ($request, $user, $originalMessageIds, $targetConversationId, $targetUserId, $tempIds) {
                $processedTempIds = [];

                foreach ($originalMessageIds as $index => $msgId) {
                    // Kiểm tra tin nhắn gốc
                    $originalMessage = Message::find($msgId);
                    if (!$originalMessage || $originalMessage->deleted_at != null) continue;

                    // Nếu có targetConversationId, kiểm tra quyền truy cập (chỉ kiểm tra 1 lần đầu để tối ưu)
                    if ($targetConversationId && $index === 0) {
                        $targetConversation = $user->conversations()->where('conversation_id', $targetConversationId)->first();
                        if (!$targetConversation) {
                            return response()->json([
                                'status_response' => 'error',
                                'message_response' => 'Bạn không có quyền gửi vào cuộc hội thoại này'
                            ]);
                        }
                    }

                    // Chuyển tiếp: Ưu tiên dùng temp_id từ client gửi lên theo vị trí mảng
                    $tempId = (isset($tempIds[$index]) && $tempIds[$index]) 
                        ? $tempIds[$index] 
                        : ('fwd_' . time() . '_' . $msgId . '_' . bin2hex(random_bytes(2)));

                    // Dispatch Job để lưu và phát Socket
                    \App\Jobs\ProcessMessageJob::dispatch(
                        $targetConversationId, 
                        $targetUserId, 
                        $user->id, 
                        $originalMessage->content, 
                        $originalMessage->type, 
                        $tempId
                    );

                    $processedTempIds[] = $tempId;
                }

                return response()->json([
                    'status_response' => 'success',
                    'message_response' => 'Đã đưa ' . count($processedTempIds) . ' tin nhắn vào hàng chờ chuyển tiếp',
                    'data' => [
                        'temp_ids' => $processedTempIds
                    ]
                ]);
            });
        } catch (\Exception $e) {
            \Log::error("Lỗi [forward]: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Không thể chuyển tiếp tin nhắn: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 7. Lấy danh sách đối tượng có thể chuyển tiếp tin nhắn (Hội thoại + Bạn bè)
     * Route: GET /api/getForwardingTargets
     */
    public function getForwardingTargets(Request $request)
    {
        try {
            $user = auth()->user();
            $search = $request->query('search');
            $targets = collect();

            // 1. Lấy danh sách hội thoại (Gần nhất và Hợp lệ)
            $queryConversations = $user->conversations()
                ->where(function($q) {
                    $q->where('conversation_user.invite_status', 1)
                      ->orWhereNull('conversation_user.invite_status');
                })
                ->with(['users' => function($q) use ($user) {
                    $q->where('users.id', '!=', $user->id);
                }])
                ->orderBy('last_update_at', 'desc');

            if ($search) {
                $queryConversations->where(function($q) use ($search) {
                    $q->where('conversations.name', 'like', "%$search%")
                      ->orWhereHas('users', function($uq) use ($search) {
                          $uq->where('users.name', 'like', "%$search%")
                            ->orWhere('users.email', $search); // Email tìm chính xác
                      });
                });
            }

            $conversations = $queryConversations->get();

            foreach ($conversations as $conv) {
                $targetName = $conv->name;
                $targetAvatar = $conv->avatar; // Dùng 'avatar' thay vì 'avatar_url'
                $partnerId = null;

                if (!$conv->is_group) {
                    $partner = $conv->users->first();
                    if ($partner) {
                        $targetName = $partner->name;
                        $targetAvatar = $partner->avatar;
                        $partnerId = $partner->id;
                    }
                }

                // Nếu có tìm kiếm, nhưng tên (sau khi xử lý) không khớp 
                // VÀ email cũng không khớp chính xác thì bỏ qua
                if ($search) {
                    $isNameMatch = str_contains(strtolower($targetName), strtolower($search));
                    $isEmailMatch = false;
                    
                    if (!$conv->is_group && $conv->users->first()) {
                        $isEmailMatch = $conv->users->first()->email === $search;
                    }
                    
                    if (!$isNameMatch && !$isEmailMatch) {
                        continue;
                    }
                }

                $targets->push([
                    'type' => 'conversation',
                    'id' => $conv->id,
                    'name' => $targetName ?? 'Chat',
                    'avatar' => $targetAvatar,
                    'is_group' => $conv->is_group,
                    'partner_id' => $partnerId
                ]);
            }

            // 2. Lấy danh sách bạn bè (Nếu đang tìm kiếm)
            if ($search) {
                $existingPartnerIds = $targets->where('type', 'conversation')->pluck('partner_id')->filter()->toArray();

                $friends = $user->friends()
                    ->where(function($q) use ($search) {
                        $q->where('name', 'like', "%$search%")
                          ->orWhere('email', $search); // Email tìm chính xác
                    })
                    ->whereNotIn('users.id', $existingPartnerIds)
                    ->get();

                foreach ($friends as $friend) {
                    $targets->push([
                        'type' => 'friend',
                        'id' => $friend->id,
                        'name' => $friend->name,
                        'avatar' => $friend->avatar,
                        'is_group' => false,
                        'partner_id' => $friend->id
                    ]);
                }
            } else {
                // Nếu không tìm kiếm, chỉ lấy 10 hội thoại đầu tiên
                $targets = $targets->take(10);
            }

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Lấy danh sách chuyển tiếp thành công',
                'data' => $targets->values()
            ]);

        } catch (\Exception $e) {
            \Log::error("Lỗi [getForwardingTargets]: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Không thể tải danh sách: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 8. Xóa tin nhắn ở phía tôi (Soft Delete)
     * Route: POST /api/deleteMessage
     */
    public function deleteMessage(Request $request)
    {
        try {
            $user = auth()->user();
            $messageId = $request->input('message_id');

            if (!$messageId) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => 'Thiếu ID tin nhắn'
                ]);
            }

            // Lưu trạng thái đã xóa cho người dùng hiện tại
            \App\Models\MessageDeletion::firstOrCreate([
                'message_id' => $messageId,
                'user_id' => $user->id
            ]);

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Đã xóa tin nhắn ở phía bạn'
            ]);
        } catch (\Exception $e) {
            \Log::error("Lỗi deleteMessage: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Lỗi khi xóa tin nhắn: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 9. Thu hồi tin nhắn (Hard Delete - Chỉ tin nhắn của mình)
     * Route: POST /api/unsendMessage
     */
    public function unsendMessage(Request $request)
    {
        try {
            $user = auth()->user();
            $messageId = $request->input('message_id');

            if (!$messageId) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => 'Thiếu ID tin nhắn'
                ]);
            }

            $message = Message::find($messageId);
            if (!$message) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => 'Tin nhắn không tồn tại'
                ]);
            }

            // Chỉ cho phép thu hồi tin nhắn của chính mình
            if ($message->sender_id !== $user->id) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => 'Bạn không có quyền thu hồi tin nhắn này'
                ]);
            }

            // Cập nhật thời gian xóa cứng
            $message->update(['deleted_at' => now()]);

            // Phát sự kiện realtime để đối phương cập nhật giao diện
            broadcast(new \App\Events\MessageUnsent($message))->toOthers();

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Đã thu hồi tin nhắn thành công',
                'data' => [
                    'id' => $message->id,
                    'deleted_at' => $message->deleted_at->toIso8601String()
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error("Lỗi unsendMessage: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Lỗi khi thu hồi tin nhắn: ' . $e->getMessage()
            ]);
        }
    }
    /**
     * Lấy danh sách Read Receipts của một cuộc trò chuyện chuyên biệt
     */
    public function getReadReceipts(Request $request)
    {
        try {
            $conversationId = $request->input('conversation_id');
            $user = auth()->user();

            if (!$conversationId) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => 'Thiếu ID cuộc hội thoại'
                ]);
            }

            $conversation = \App\Models\Conversation::find($conversationId);
            if (!$conversation) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => 'Không tìm thấy cuộc trò chuyện'
                ]);
            }

            // Lấy danh sách Read Receipts (trừ bản thân mình)
            $readReceipts = $conversation->users()
                ->where('users.id', '!=', $user->id)
                ->wherePivot('invite_status', '1')
                ->get(['users.id as user_id', 'users.name', 'users.avatar', 'last_read_id', 'last_delivered_id']);

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Lấy Read Receipts thành công',
                'data' => [
                    'read_receipts' => $readReceipts
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error("Lỗi [getReadReceipts]: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Lỗi khi lấy trạng thái đã xem: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 10. Xóa toàn bộ lịch sử trò chuyện (Xóa phía tôi)
     */
    public function clearConversation(Request $request)
    {
        try {
            $user = auth()->user();
            $conversationId = $request->input('conversation_id');

            if (!$conversationId) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => 'Thiếu ID cuộc hội thoại'
                ]);
            }

            // Lấy ID tin nhắn mới nhất hiện tại để đánh dấu đã xem hết khi xóa
            $lastMessageId = Message::where('conversation_id', $conversationId)->max('id');

            // Cập nhật cleared_at và last_read_id cho người dùng hiện tại
            DB::table('conversation_user')
                ->where('user_id', $user->id)
                ->where('conversation_id', $conversationId)
                ->update([
                    'cleared_at' => now(),
                    'last_read_id' => $lastMessageId
                ]);

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Đã xóa lịch sử trò chuyện thành công'
            ]);
        } catch (\Exception $e) {
            \Log::error("Lỗi clearConversation: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Lỗi khi xóa lịch sử trò chuyện: ' . $e->getMessage()
            ]);
        }
    }
}
