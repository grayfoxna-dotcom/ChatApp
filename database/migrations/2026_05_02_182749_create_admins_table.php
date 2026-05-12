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
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->unique()->comment('Tên đăng nhập Admin');
            $table->string('email', 100)->unique()->comment('Email quản trị');
            $table->timestamp('email_verified_at')->nullable()->comment('Thời điểm xác thực email');
            $table->string('password', 100)->comment('Mật khẩu đã mã hóa');
            $table->string('avatar', 1000)->nullable()->comment('Đường dẫn ảnh đại diện');
            $table->tinyInteger('status')->default(0)->comment('Trạng thái: 0: Chưa duyệt, 1: Đã duyệt, 2: Khóa');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
