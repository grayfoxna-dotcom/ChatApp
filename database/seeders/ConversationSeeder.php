<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Relationship;
use Illuminate\Support\Facades\DB;

class ConversationSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();
        if ($users->count() < 4) return;

        $user1 = $users[0]; // Duc Tran (bluefoxna)
        $user2 = $users[1]; // Duck Tran (grayfoxna)
        $user3 = $users[2]; // Minh Anh
        $user4 = $users[3]; // Thanh Hà

        // --- KỊCH BẢN NHÓM ---

        // 1. Duck Tran (U2) mời Duc Tran (U1) vào 2 nhóm
        for ($i = 1; $i <= 2; $i++) {
            $group = Conversation::create([
                'name' => "Nhóm dự án Duck Tran $i",
                'avatar' => '/default_avatar_group.jpg',
                'is_group' => true,
                'invite_id' => $user2->id,
                'last_update_at' => now(),
            ]);
            $group->users()->sync([
                $user2->id => ['invite_status' => '1'],
                $user1->id => ['invite_status' => '0'],
            ]);
            // Tạo tin nhắn thông báo hệ thống
            $group->messages()->create([
                'content' => "{$user2->name} đã tạo nhóm",
                'sender_id' => null,
                'type' => 'notification',
                'created_at' => now()->subHours(2),
            ]);
        }

        // 2. Minh Anh (U3) mời Duc Tran (U1) và Duck Tran (U2) vào 2 nhóm
        for ($i = 1; $i <= 2; $i++) {
            $group = Conversation::create([
                'name' => "Team Building Minh Anh $i",
                'avatar' => '/default_avatar_group.jpg',
                'is_group' => true,
                'invite_id' => $user3->id,
                'last_update_at' => now(),
            ]);
            $group->users()->sync([
                $user3->id => ['invite_status' => '1'],
                $user1->id => ['invite_status' => '0'],
                $user2->id => ['invite_status' => '0'],
            ]);
            // Tạo tin nhắn thông báo hệ thống
            $group->messages()->create([
                'content' => "{$user3->name} đã tạo nhóm",
                'sender_id' => null,
                'type' => 'notification',
                'created_at' => now()->subHours(2),
            ]);
        }

        // 3. Thanh Hà (U4) mời TẤT CẢ mọi người còn lại vào 1 nhóm lớn
        $bigGroup = Conversation::create([
            'name' => "Gia đình Thanh Hà",
            'avatar' => '/default_avatar_group.jpg',
            'is_group' => true,
            'invite_id' => $user4->id,
            'last_update_at' => now(),
        ]);
        
        $allOtherUsers = User::where('id', '!=', $user4->id)->get();
        $syncData = [$user4->id => ['invite_status' => '1']]; // Thanh Hà là admin (1)
        
        foreach ($allOtherUsers as $u) {
            // U1 (Duc Tran) không đồng ý (0), tất cả mọi người khác đều đồng ý (1)
            $syncData[$u->id] = [
                'invite_status' => $u->id == $user1->id ? '0' : '1'
            ];
        }
        $bigGroup->users()->sync($syncData);

        // Tạo tin nhắn thông báo hệ thống
        $bigGroup->messages()->create([
            'content' => "{$user4->name} đã tạo nhóm",
            'sender_id' => null,
            'type' => 'notification',
            'created_at' => now()->subHours(2),
        ]);


        // --- KỊCH BẢN KẾT BẠN (BẢNG relationships) ---

        // Helper tạo lời mời kết bạn (Pending)
        $createFriendRequest = function($senderId, $receiverId) {
            Relationship::create([
                'requester_id' => $senderId,
                'addressee_id' => $receiverId,
                'status' => 'pending',
            ]);
        };

        // Helper tạo bạn bè chính thức (Accepted) và sinh phòng chat 1-1 tương ứng
        $createOfficialFriends = function($userA_Id, $userB_Id) {
            Relationship::create([
                'requester_id' => $userA_Id,
                'addressee_id' => $userB_Id,
                'status' => 'accepted',
            ]);

            $conversation = Conversation::create([
                'is_group' => false,
                'invite_id' => $userA_Id,
                'last_update_at' => now()
            ]);

            $conversation->users()->sync([
                $userA_Id => ['invite_status' => '1'],
                $userB_Id => ['invite_status' => '1'],
            ]);

            // Tạo tin nhắn thông báo hệ thống
            $conversation->messages()->create([
                'content' => 'Các bạn hiện đã có thể trò chuyện với nhau',
                'sender_id' => null,
                'type' => 'notification',
                'created_at' => now()->subHours(1),
            ]);
        };

        // --- SEED DỮ LIỆU THỰC TẾ ---

        // 1. Tạo một số cặp đã là bạn bè chính thức (được quyền nhắn tin trực tiếp)
        $createOfficialFriends($user1->id, $user2->id); // Duc Tran và Duck Tran
        $createOfficialFriends($user1->id, $user3->id); // Duc Tran và Minh Anh

        // 2. Tạo một số lời mời kết bạn đang chờ duyệt (Pending) gửi tới Duc Tran (U1)
        $createFriendRequest($user4->id, $user1->id); // Thanh Hà gửi cho Duc Tran

        // 3. Tạo lời mời từ các người dùng ngẫu nhiên khác gửi tới Duc Tran (U1)
        $restOfUsers = User::whereNotIn('id', [$user1->id, $user2->id, $user3->id, $user4->id])->get();
        foreach ($restOfUsers as $u) {
            $createFriendRequest($u->id, $user1->id);
        }

        // // --- 4. TẠO ĐOẠN HỘI THOẠI MẪU (DUC TRAN & DUCK TRAN) ---
        // $sampleConv = Conversation::whereHas('users', function($q) use ($user1) {
        //     $q->where('user_id', $user1->id);
        // })->whereHas('users', function($q) use ($user2) {
        //     $q->where('user_id', $user2->id);
        // })->where('is_group', false)->first();

        // if ($sampleConv) {
        //     $sampleConv->messages()->createMany([
        //         ['sender_id' => $user2->id, 'content' => ['text' => 'Chào Duc Tran, bạn khỏe không?'], 'type' => 'text', 'created_at' => now()->subMinutes(5)],
        //         ['sender_id' => $user1->id, 'content' => ['text' => 'Chào Duck Tran, mình khỏe, cảm ơn bạn!'], 'type' => 'text', 'created_at' => now()->subMinutes(4)],
        //         ['sender_id' => $user2->id, 'content' => ['text' => 'Bạn đã xem bản cập nhật mới về tính năng thu hồi tin nhắn chưa?'], 'type' => 'text', 'created_at' => now()->subMinutes(3)],
        //         ['sender_id' => $user1->id, 'content' => ['text' => 'Mình đang xem đây, hoạt động rất mượt mà!'], 'type' => 'text', 'created_at' => now()->subMinutes(2)],
        //         ['sender_id' => $user2->id, 'content' => ['text' => 'Tuyệt vời, hy vọng người dùng sẽ thích nó.'], 'type' => 'text', 'created_at' => now()->subMinutes(1)],
        //     ]);
            
        //     // Cập nhật lại thời gian hội thoại cuối cùng
        //     $sampleConv->update(['last_update_at' => now()]);
        // }
    }
}
