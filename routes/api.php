<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\MessageController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'profile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    Route::post('/heartbeat', [AuthController::class, 'heartbeat']);
    
    // Contact & Friend Relationships
    Route::get('/search', [ContactController::class, 'search']);
    Route::get('/getFriendRequests', [ContactController::class, 'getFriendRequests']);
    Route::get('/getSentFriendRequests', [ContactController::class, 'getSentFriendRequests']);
    // --- QUẢN LÝ DANH BẠ & BẠN BÈ (ContactController) ---
    // Gửi lời mời kết bạn
    Route::post('/sendFriendRequest', [ContactController::class, 'sendFriendRequest']);
    // Chấp nhận lời mời kết bạn
    Route::post('/acceptFriendRequest', [ContactController::class, 'acceptFriendRequest']);
    // Từ chối lời mời kết bạn
    Route::post('/declineFriendRequest', [ContactController::class, 'declineFriendRequest']);
    // Hủy lời mời kết bạn đã gửi
    Route::post('/cancelFriendRequest', [ContactController::class, 'cancelFriendRequest']);
    // Hủy kết bạn
    Route::post('/unfriend', [ContactController::class, 'unfriend']);
    // Lấy danh sách bạn bè
    Route::get('/getFriends', [ContactController::class, 'getFriends']);
    // Lấy danh sách bạn bè đang online
    Route::get('/getActiveFriends', [ContactController::class, 'getActiveFriends']);
    // Gợi ý bạn bè mới
    Route::get('/getSuggestedFriends', [ContactController::class, 'getSuggestedFriends']);
    // Chặn người dùng
    Route::post('/blockUser', [ContactController::class, 'blockUser']);
    // Lấy danh sách người dùng đã chặn
    Route::get('/getBlockedUsers', [ContactController::class, 'getBlockedUsers']);
    // Bỏ chặn người dùng
    Route::post('/unblockUser', [ContactController::class, 'unblockUser']);



    // --- QUẢN LÝ NHÓM & LỜI MỜI VÀO NHÓM (ContactController) ---
    // Lấy danh sách lời mời vào nhóm
    Route::get('/getGroupInvitations', [ContactController::class, 'getGroupInvitations']);
    // Chấp nhận vào nhóm
    Route::post('/acceptGroupInvite', [ContactController::class, 'acceptGroupInvite']);
    // Từ chối vào nhóm
    Route::post('/declineGroupInvite', [ContactController::class, 'declineGroupInvite']);
    // Tạo nhóm chat mới
    Route::post('/createGroup', [ContactController::class, 'createGroup']);
    // Rời khỏi nhóm chat
    Route::post('/leaveGroup', [ContactController::class, 'leaveGroup']);

    // --- HỆ THỐNG TIN NHẮN & HỘI THOẠI (MessageController) ---
    // Lấy danh sách các cuộc hội thoại
    Route::get('/getConversations', [MessageController::class, 'getConversations']);
    // Lấy danh sách tin nhắn trong một cuộc hội thoại
    Route::get('/getMessages', [MessageController::class, 'getMessages']);
    // Đồng bộ tin nhắn vi sai (Incremental Sync)
    Route::get('/syncMessages', [MessageController::class, 'syncMessages']);
    // Lấy trạng thái đã xem (Read Receipts) chuyên biệt
    Route::get('/getReadReceipts', [MessageController::class, 'getReadReceipts']);
    // Gửi tin nhắn mới (văn bản, hình ảnh, video, ghi âm, tệp)
    Route::post('/sendMessage', [MessageController::class, 'sendMessage']);
    // Đánh dấu tin nhắn đã nhận (Delivered)
    Route::post('/markDelivered', [MessageController::class, 'markDelivered']);
    // Đánh dấu tin nhắn đã xem (Read)
    Route::post('/markRead', [MessageController::class, 'markRead']);
    // Chuyển tiếp tin nhắn sang hội thoại khác
    Route::post('/forwardMessage', [MessageController::class, 'forwardMessage']);
    // Tìm kiếm đối tượng để chuyển tiếp tin nhắn
    Route::get('/getForwardingTargets', [MessageController::class, 'getForwardingTargets']);
    // Xóa tin nhắn (chỉ phía tôi)
    Route::post('/deleteMessage', [MessageController::class, 'deleteMessage']);
    // Thu hồi tin nhắn (cho tất cả mọi người)
    Route::post('/unsendMessage', [MessageController::class, 'unsendMessage']);
    // Xóa sạch lịch sử trò chuyện (chỉ phía tôi)
    Route::post('/clearConversation', [MessageController::class, 'clearConversation']);
});

