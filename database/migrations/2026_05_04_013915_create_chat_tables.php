<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Bảng Hội thoại (Conversations)
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->dateTime('last_update_at')->nullable()->comment('Thời gian cập nhật cuối cùng');
            $table->boolean('is_group')->default(false)->comment('Có phải nhóm hay không');
            $table->string('name')->nullable()->comment('Tên nhóm');
            $table->string('avatar')->nullable()->comment('Ảnh đại diện nhóm');
            $table->unsignedBigInteger('invite_id')->nullable()->comment('ID người gửi lời mời kết bạn');
            $table->timestamps();
        });

        // 2. Bảng trung gian Hội thoại - Người dùng (Conversation_User)
        Schema::create('conversation_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('ID người dùng');
            $table->unsignedBigInteger('conversation_id')->comment('ID cuộc hội thoại');
            $table->string('invite_status', 10)->nullable()->default('0')->comment('0: chờ, 1: chấp nhận');
            $table->unsignedBigInteger('last_read_id')->nullable()->comment('ID tin nhắn cuối cùng đã đọc');
            $table->unsignedBigInteger('last_delivered_id')->nullable()->comment('ID tin nhắn cuối cùng đã nhận');
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
        });

        // 3. Bảng Tin nhắn (Messages)
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id')->comment('ID cuộc hội thoại');
            $table->unsignedBigInteger('sender_id')->nullable()->comment('ID người gửi (null nếu là tin nhắn hệ thống)');
            $table->json('content')->comment('Nội dung tin nhắn (JSON)');
            $table->string('type', 20)->default('text')->comment('Loại tin nhắn (text, image, file...)');
            $table->unsignedBigInteger('reply_to_id')->nullable()->comment('ID tin nhắn trích dẫn');
            $table->timestamp('deleted_at')->nullable()->comment('Thời gian thu hồi tin nhắn');
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reply_to_id')->references('id')->on('messages')->onDelete('set null');
        });

        // 4. Bảng Xóa tin nhắn cá nhân (Message Deletions)
        Schema::create('message_deletions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['message_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_deletions');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_user');
        Schema::dropIfExists('conversations');
    }
};
