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
        Schema::create('relationships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requester_id')->comment('ID người gửi yêu cầu kết bạn');
            $table->unsignedBigInteger('addressee_id')->comment('ID người nhận yêu cầu kết bạn');
            $table->string('status', 20)->default('pending')->comment('pending, accepted, blocked');
            $table->timestamps();

            // Khóa ngoại liên kết tới bảng users
            $table->foreign('requester_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('addressee_id')->references('id')->on('users')->onDelete('cascade');

            // Đảm bảo không tồn tại trùng lặp mối quan hệ giữa hai người dùng
            $table->unique(['requester_id', 'addressee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relationships');
    }
};
