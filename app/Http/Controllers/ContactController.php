<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Relationship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    /**
     * Tìm kiếm người dùng và nhóm.
     * Route: GET /api/search
     */
    public function search(Request $request)
    {
        try {
            $query = $request->input('query');
            $currentUser = Auth::user();

            if (!$query) {
                return response()->json(['status_response' => 'success', 'data' => []]);
            }

            // 1. Tìm kiếm người dùng trong hệ thống (loại trừ chính mình)
            $users = User::where('id', '!=', $currentUser->id)
                ->where(function($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('email', '=', $query);
                })
                ->get()
                ->map(function($user) use ($currentUser) {
                    $user->result_type = 'user';

                    // Kiểm tra xem đã là bạn bè (status === 'accepted') hay chưa
                    $isFriend = Relationship::where('status', 'accepted')
                        ->where(function($q) use ($currentUser, $user) {
                            $q->where(function($inner) use ($currentUser, $user) {
                                $inner->where('requester_id', $currentUser->id)->where('addressee_id', $user->id);
                            })->orWhere(function($inner) use ($currentUser, $user) {
                                $inner->where('requester_id', $user->id)->where('addressee_id', $currentUser->id);
                            });
                        })
                        ->exists();

                    $user->relationship_status = $isFriend ? 'is_friend' : 'none';
                    return $user;
                });

            // 2. Tìm kiếm nhóm mà mình đã chấp nhận tham gia
            $groups = Conversation::where('is_group', true)
                ->where('name', 'LIKE', "%{$query}%")
                ->whereHas('users', function($q) use ($currentUser) {
                    // Sửa lỗi truy cập cột pivot: dùng tên cột trực tiếp vì subquery đã join bảng pivot
                    $q->where('user_id', $currentUser->id)->where('invite_status', '1');
                })
                ->get()
                ->map(function($group) {
                    $group->result_type = 'group';
                    return $group;
                });

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Tìm kiếm hoàn tất',
                'data' => $users->concat($groups)
            ]);
        } catch (\Exception $e) {
            \Log::error("Lỗi search: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Lỗi tìm kiếm: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Lấy danh sách lời mời kết bạn cá nhân NHẬN ĐƯỢC đang chờ duyệt.
     * Route: GET /api/getFriendRequests
     */
    public function getFriendRequests(Request $request)
    {
        try {
            $currentUser = Auth::user();

            // Lời mời kết bạn NHẬN ĐƯỢC - Sử dụng Eager Loading tối ưu hiệu năng
            $receivedRequests = Relationship::with('requester')
                ->where('addressee_id', $currentUser->id)
                ->where('status', 'pending')
                ->latest()
                ->get()
                ->map(function($rel) {
                    return [
                        'relationship_id' => $rel->id,
                        'is_group' => false,
                        'inviter' => $rel->requester
                    ];
                });

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Lấy danh sách lời mời kết bạn nhận được thành công',
                'data' => [
                    'friend_requests' => $receivedRequests,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error("Lỗi getFriendRequests: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Lỗi lấy lời mời kết bạn: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Lấy danh sách lời mời kết bạn cá nhân ĐÃ GỬI đang chờ đối phương duyệt.
     * Route: GET /api/getSentFriendRequests
     */
    public function getSentFriendRequests(Request $request)
    {
        $currentUser = Auth::user();

        // Lời mời kết bạn ĐÃ GỬI - Sử dụng Eager Loading tối ưu hiệu năng
        $sentFriendRequests = Relationship::with('addressee')
            ->where('requester_id', $currentUser->id)
            ->where('status', 'pending')
            ->latest()
            ->get()
            ->map(function($rel) {
                return [
                    'relationship_id' => $rel->id,
                    'is_group' => false,
                    'receiver' => $rel->addressee
                ];
            });

        return response()->json([
            'status_response' => 'success',
            'message_response' => 'Lấy danh sách lời mời kết bạn đã gửi thành công',
            'data' => [
                'sent_friend_requests' => $sentFriendRequests,
            ]
        ]);
    }

    /**
     * Gửi lời mời kết bạn mới.
     * Route: POST /api/sendFriendRequest
     */
    public function sendFriendRequest(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id'
            ], [
                'user_id.required' => 'Thiếu mã người dùng.',
                'user_id.exists' => 'Người dùng không tồn tại.'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => $validator->errors()->first(),
                ]);
            }

            $senderId = Auth::id();
            $receiverId = $request->input('user_id');

            if ($senderId == $receiverId) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => 'Không thể kết bạn với chính mình.'
                ]);
            }

            // Kiểm tra xem đã có quan hệ nào chưa
            $existingRel = Relationship::where(function($q) use ($senderId, $receiverId) {
                    $q->where('requester_id', $senderId)->where('addressee_id', $receiverId);
                })
                ->orWhere(function($q) use ($senderId, $receiverId) {
                    $q->where('requester_id', $receiverId)->where('addressee_id', $senderId);
                })
                ->first();

            if ($existingRel) {
                if ($existingRel->status === 'accepted') {
                    return response()->json([
                        'status_response' => 'error',
                        'message_response' => 'Đã là bạn bè.',
                    ]);
                } elseif ($existingRel->status === 'pending') {
                    return response()->json([
                        'status_response' => 'error',
                        'message_response' => 'Yêu cầu kết bạn đang chờ xử lý.',
                    ]);
                }
            }

            Relationship::create([
                'requester_id' => $senderId,
                'addressee_id' => $receiverId,
                'status' => 'pending',
            ]);

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Đã gửi lời mời kết bạn.'
            ]);
        } catch (\Exception $e) {
            \Log::error("Lỗi sendFriendRequest: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Lỗi gửi lời mời kết bạn: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Chấp nhận lời mời kết bạn cá nhân.
     * Route: POST /api/acceptFriendRequest
     */
    public function acceptFriendRequest(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'relationship_id' => 'required|exists:relationships,id'
            ], [
                'relationship_id.required' => 'Thiếu mã yêu cầu kết bạn.'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => $validator->errors()->first(),
                ]);
            }

            $result = DB::transaction(function() use ($request) {
                $relationship = Relationship::find($request->input('relationship_id'));

                // Cập nhật trạng thái mối quan hệ thành accepted
                $relationship->update(['status' => 'accepted']);

                // Tự động tạo cuộc hội thoại 1-1 nếu chưa tồn tại giữa 2 bên
                $requesterId = $relationship->requester_id;
                $addresseeId = $relationship->addressee_id;

                $existingConversation = Conversation::where('is_group', false)
                    ->whereHas('users', function ($q) use ($requesterId) {
                        $q->where('user_id', $requesterId);
                    })
                    ->whereHas('users', function ($q) use ($addresseeId) {
                        $q->where('user_id', $addresseeId);
                    })
                    ->first();

                if (!$existingConversation) {
                    $conversation = Conversation::create([
                        'is_group' => false,
                        'last_update_at' => now(),
                    ]);
                    $conversation->users()->attach([$requesterId, $addresseeId], ['invite_status' => '1']);
                }

                return true;
            });

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Đã chấp nhận kết bạn và tạo cuộc trò chuyện mới.'
            ]);
        } catch (\Exception $e) {
            \Log::error("Lỗi acceptFriendRequest: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Lỗi chấp nhận kết bạn: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Từ chối yêu cầu kết bạn cá nhân.
     * Route: POST /api/declineFriendRequest
     */
    public function declineFriendRequest(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'relationship_id' => 'required|exists:relationships,id'
        ], [
            'relationship_id.required' => 'Thiếu mã yêu cầu kết bạn.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => $validator->errors()->first(),
            ]);
        }

        Relationship::destroy($request->relationship_id);

        return response()->json([
            'status_response' => 'success',
            'message_response' => 'Đã từ chối yêu cầu kết bạn.'
        ]);
    }

    /**
     * Hủy lời mời kết bạn đã gửi.
     * Route: POST /api/cancelFriendRequest
     */
    public function cancelFriendRequest(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Dữ liệu không hợp lệ.'
            ]);
        }

        $currentUser = Auth::user();
        $targetUserId = $request->user_id;

        // Xóa lời mời trạng thái pending bằng Model Relationship
        Relationship::where('requester_id', $currentUser->id)
            ->where('addressee_id', $targetUserId)
            ->where('status', 'pending')
            ->delete();

        return response()->json([
            'status_response' => 'success',
            'message_response' => 'Đã hủy lời mời kết bạn.'
        ]);
    }

    /**
     * Hủy kết bạn (Xóa mối quan hệ).
     * Route: POST /api/unfriend
     */
    public function unfriend(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ], [
            'user_id.required' => 'Thiếu mã người dùng.',
            'user_id.exists' => 'Người dùng không tồn tại.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => $validator->errors()->first(),
            ]);
        }

        $currentUser = Auth::user();
        $friendId = $request->user_id;

        // Xóa mối quan hệ bằng Model Relationship
        Relationship::where(function($q) use ($currentUser, $friendId) {
                $q->where('requester_id', $currentUser->id)->where('addressee_id', $friendId);
            })
            ->orWhere(function($q) use ($currentUser, $friendId) {
                $q->where('requester_id', $friendId)->where('addressee_id', $currentUser->id);
            })
            ->delete();

        return response()->json([
            'status_response' => 'success',
            'message_response' => 'Đã hủy kết bạn.'
        ]);
    }

    /**
     * Lấy danh sách tất cả bạn bè.
     * Route: GET /api/getFriends
     */
    public function getFriends()
    {
        $currentUser = Auth::user();

        // Lấy danh sách ID những người bạn đã đồng ý kết bạn bằng Model Relationship (mới nhất lên đầu)
        $friendIds = Relationship::where('status', 'accepted')
            ->where(function($q) use ($currentUser) {
                $q->where('requester_id', $currentUser->id)
                  ->orWhere('addressee_id', $currentUser->id);
            })
            ->latest('updated_at')
            ->get()
            ->map(function($rel) use ($currentUser) {
                return $rel->requester_id == $currentUser->id ? $rel->addressee_id : $rel->requester_id;
            })
            ->toArray();

        $friends = User::select('id', 'name', 'avatar', 'last_seen_at')
            ->whereIn('id', $friendIds)
            ->get()
            ->sortBy(function($user) use ($friendIds) {
                return array_search($user->id, $friendIds);
            })
            ->values();

        return response()->json([
            'status_response' => 'success',
            'data' => $friends
        ]);
    }

    /**
     * Lấy danh sách bạn bè đang hoạt động.
     * Route: GET /api/getActiveFriends
     */
    public function getActiveFriends()
    {
        $currentUser = Auth::user();
        $activeThreshold = now()->subMinutes(2);

        // Lấy danh sách ID những người bạn đã đồng ý kết bạn bằng Model Relationship
        $friendIds = Relationship::where('status', 'accepted')
            ->where(function($q) use ($currentUser) {
                $q->where('requester_id', $currentUser->id)
                  ->orWhere('addressee_id', $currentUser->id);
            })
            ->get()
            ->map(function($rel) use ($currentUser) {
                return $rel->requester_id == $currentUser->id ? $rel->addressee_id : $rel->requester_id;
            });

        $friends = User::select('id', 'name', 'avatar', 'last_seen_at')
            ->whereIn('id', $friendIds)
            ->where('last_seen_at', '>=', $activeThreshold)
            ->get();

        return response()->json([
            'status_response' => 'success',
            'data' => $friends
        ]);
    }

    /**
     * Lấy danh sách bạn bè gợi ý (Cùng chung nhóm nhưng chưa có liên kết bạn bè).
     * Route: GET /api/getSuggestedFriends
     */
    public function getSuggestedFriends()
    {
        $currentUser = Auth::user();

        // 1. Lấy danh sách ID các nhóm mà user hiện tại ĐÃ CHẤP NHẬN tham gia
        $groupConversationIds = $currentUser->conversations()
            ->where('is_group', true)
            ->where('conversation_user.invite_status', '1')
            ->pluck('conversations.id');

        // 2. Tìm user trong các nhóm đó, loại trừ chính mình và những người đã có mối quan hệ bạn bè
        $suggestedFriends = User::select('id', 'name', 'avatar', 'last_seen_at')
            ->where('users.id', '!=', $currentUser->id)
            ->whereHas('conversations', function ($q) use ($groupConversationIds) {
                $q->whereIn('conversations.id', $groupConversationIds)
                  ->where('conversation_user.invite_status', '1');
            })
            ->whereNotExists(function ($q) use ($currentUser) {
                $q->select(DB::raw(1))
                  ->from('relationships')
                  ->where(function($sub) use ($currentUser) {
                      $sub->whereRaw('relationships.requester_id = users.id AND relationships.addressee_id = ?', [$currentUser->id])
                          ->orWhereRaw('relationships.addressee_id = users.id AND relationships.requester_id = ?', [$currentUser->id]);
                  });
            })
            ->inRandomOrder()
            ->take(10)
            ->get();

        return response()->json([
            'status_response' => 'success',
            'data' => $suggestedFriends
        ]);
    }

    /**
     * Lấy danh sách lời mời vào nhóm đang chờ.
     * Route: GET /api/getGroupInvitations
     */
    public function getGroupInvitations(Request $request)
    {
        try {
            $currentUser = Auth::user();

            $groupInvitations = Conversation::where('is_group', true)
                ->whereHas('users', function($q) use ($currentUser) {
                    // Sửa lỗi truy cập cột pivot
                    $q->where('user_id', $currentUser->id)
                      ->where('invite_status', '0');
                })
                ->latest()
                ->with(['inviter', 'users'])
                ->get()
                ->map(function($conv) {
                    return [
                        'conversation_id' => $conv->id,
                        'is_group' => true,
                        'name' => $conv->name,
                        'avatar' => $conv->avatar,
                        'inviter' => $conv->inviter
                    ];
                });

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Lấy danh sách lời mời vào nhóm thành công',
                'data' => [
                    'group_invitations' => $groupInvitations,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error("Lỗi getGroupInvitations: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Lỗi lấy lời mời nhóm: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Chấp nhận lời mời vào nhóm.
     * Route: POST /api/acceptGroupInvite
     */
    public function acceptGroupInvite(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'conversation_id' => 'required|exists:conversations,id'
        ], [
            'conversation_id.required' => 'Thiếu mã nhóm.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => $validator->errors()->first(),
            ]);
        }

        $currentUser = Auth::user();
        $conversation = Conversation::find($request->conversation_id);

        if (!$conversation || !$conversation->is_group) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Nhóm không tồn tại.'
            ]);
        }

        $conversation->users()->updateExistingPivot($currentUser->id, ['invite_status' => '1']);

        return response()->json([
            'status_response' => 'success',
            'message_response' => 'Đã chấp nhận vào nhóm.'
        ]);
    }

    /**
     * Từ chối lời mời vào nhóm.
     * Route: POST /api/declineGroupInvite
     */
    public function declineGroupInvite(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'conversation_id' => 'required|exists:conversations,id'
        ], [
            'conversation_id.required' => 'Thiếu mã nhóm.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => $validator->errors()->first(),
            ]);
        }

        $currentUser = Auth::user();
        $conversation = Conversation::find($request->conversation_id);

        if ($conversation && $conversation->is_group) {
            $conversation->users()->detach($currentUser->id);
        }

        return response()->json([
            'status_response' => 'success',
            'message_response' => 'Đã từ chối lời mời vào nhóm.'
        ]);
    }

    /**
     * Tạo nhóm mới.
     * Route: POST /api/createGroup
     */
    public function createGroup(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'user_ids' => 'required', // Nhận dưới dạng JSON string từ Multipart
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ], [
                'name.required' => 'Vui lòng nhập tên nhóm',
                'avatar.image' => 'File tải lên phải là hình ảnh',
                'avatar.mimes' => 'Ảnh đại diện chỉ chấp nhận định dạng: jpeg, png, jpg, gif',
                'avatar.max' => 'Dung lượng ảnh không được quá 2MB',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => $validator->errors()->first(),
                ]);
            }

            $currentUser = Auth::user();
            
            // Giải mã user_ids từ JSON string
            $userIds = json_decode($request->input('user_ids'), true);
            
            if (!is_array($userIds) || empty($userIds)) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => 'Danh sách thành viên không hợp lệ.'
                ]);
            }
            
            // Thêm chính người tạo vào danh sách thành viên
            if (!in_array($currentUser->id, $userIds)) {
                $userIds[] = $currentUser->id;
            }

            $conversation = DB::transaction(function() use ($request, $currentUser, $userIds) {
                // Xử lý upload avatar
                $avatarPath = '/default_avatar_group.jpg'; 
                if ($request->hasFile('avatar')) {
                    $path = $request->file('avatar')->store('avatars', 'public');
                    $avatarPath = asset('storage/' . $path);
                }

                // Tạo cuộc hội thoại nhóm
                $conversation = Conversation::create([
                    'name' => $request->input('name'),
                    'avatar' => $avatarPath,
                    'is_group' => true,
                    'invite_id' => $currentUser->id,
                    'last_update_at' => now()
                ]);

                // Chuẩn bị dữ liệu cho bảng pivot
                $pivotData = [];
                foreach ($userIds as $userId) {
                    $pivotData[$userId] = [
                        'invite_status' => ($userId == $currentUser->id) ? '1' : '0'
                    ];
                }

                // Thêm tất cả thành viên vào nhóm với trạng thái tương ứng
                $conversation->users()->attach($pivotData);
                
                return $conversation;
            });

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Tạo nhóm thành công.',
                'data' => $conversation->load('users')
            ]);
        } catch (\Exception $e) {
            \Log::error("Lỗi createGroup: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Lỗi tạo nhóm: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Rời khỏi nhóm.
     * Route: POST /api/leaveGroup
     */
    public function leaveGroup(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'conversation_id' => 'required|exists:conversations,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => 'Dữ liệu không hợp lệ.'
                ]);
            }

            $currentUser = Auth::user();
            $conversation = Conversation::find($request->input('conversation_id'));

            if (!$conversation || !$conversation->is_group) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => 'Nhóm không tồn tại.'
                ]);
            }

            DB::transaction(function() use ($conversation, $currentUser) {
                // Rời khỏi nhóm (xóa khỏi bảng pivot)
                $conversation->users()->detach($currentUser->id);

                // Nếu nhóm không còn ai, xóa nhóm
                if ($conversation->users()->count() === 0) {
                    $conversation->delete();
                }
            });

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Đã rời khỏi nhóm.'
            ]);
        } catch (\Exception $e) {
            \Log::error("Lỗi leaveGroup: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Lỗi rời nhóm: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Lấy danh sách người dùng đã chặn.
     * Route: GET /api/getBlockedUsers
     */
    public function getBlockedUsers()
    {
        try {
            $currentUser = Auth::user();
            $blockedUsers = Relationship::where('requester_id', $currentUser->id)
                ->where('status', 'blocked')
                ->with('addressee')
                ->get()
                ->pluck('addressee');

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Lấy danh sách chặn thành công',
                'data' => $blockedUsers
            ]);
        } catch (\Exception $e) {
            \Log::error("Lỗi getBlockedUsers: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Lỗi khi lấy danh sách chặn: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Bỏ chặn người dùng.
     * Route: POST /api/unblockUser
     */
    public function unblockUser(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => 'Dữ liệu không hợp lệ.'
                ]);
            }

            $currentUser = Auth::user();
            $targetUserId = $request->input('user_id');

            DB::transaction(function() use ($currentUser, $targetUserId) {
                Relationship::where('requester_id', $currentUser->id)
                    ->where('addressee_id', $targetUserId)
                    ->where('status', 'blocked')
                    ->delete();
            });

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Đã bỏ chặn người dùng.'
            ]);
        } catch (\Exception $e) {
            \Log::error("Lỗi unblockUser: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Lỗi khi bỏ chặn: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Chặn người dùng.
     * Route: POST /api/blockUser
     */
    public function blockUser(Request $request)
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => 'Dữ liệu không hợp lệ.'
                ]);
            }

            $currentUser = Auth::user();
            $targetUserId = $request->input('user_id');

            if ($currentUser->id == $targetUserId) {
                return response()->json([
                    'status_response' => 'error',
                    'message_response' => 'Không thể chặn chính mình.'
                ]);
            }

            DB::transaction(function() use ($currentUser, $targetUserId) {
                Relationship::where(function($q) use ($currentUser, $targetUserId) {
                    $q->where('requester_id', $currentUser->id)->where('addressee_id', $targetUserId);
                })->orWhere(function($q) use ($currentUser, $targetUserId) {
                    $q->where('requester_id', $targetUserId)->where('addressee_id', $currentUser->id);
                })->delete();

                Relationship::create([
                    'requester_id' => $currentUser->id,
                    'addressee_id' => $targetUserId,
                    'status' => 'blocked'
                ]);
            });

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Đã chặn người dùng thành công.'
            ]);
        } catch (\Exception $e) {
            \Log::error("Lỗi blockUser: " . $e->getMessage());
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Lỗi khi chặn người dùng: ' . $e->getMessage()
            ]);
        }
    }
}

