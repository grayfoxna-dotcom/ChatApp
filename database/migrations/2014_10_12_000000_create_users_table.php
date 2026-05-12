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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->comment('Tên hiển thị người dùng');
            $table->string('email', 100)->unique()->comment('Email đăng nhập');
            $table->string('avatar', 1000)->nullable()->comment('Đường dẫn ảnh đại diện');
            $table->timestamp('email_verified_at')->nullable()->comment('Thời điểm xác thực email');
            $table->string('password', 100)->comment('Mật khẩu đã mã hóa');
            $table->tinyInteger('isActive')->default(1)->comment('Trạng thái: 0: Bị khóa, 1: Hoạt động');
            $table->timestamp('last_seen_at')->nullable()->comment('Thời gian hoạt động cuối cùng');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
