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
        // 1. Bảng Vai trò
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // vd: super_admin
            $table->string('display_name');  // vd: Quản trị tối cao
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // 2. Bảng Quyền hạn
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // vd: users.view
            $table->string('display_name');  // vd: Xem danh sách người dùng
            $table->string('group')->comment('Nhóm quyền: user, admin, settings...');
            $table->timestamps();
        });

        // 3. Bảng trung gian Vai trò - Quyền hạn
        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->primary(['role_id', 'permission_id']);
        });

        // 4. Bảng trung gian Admin - Vai trò
        Schema::create('admin_role', function (Blueprint $table) {
            $table->foreignId('admin_id')->constrained('admins')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->primary(['admin_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_role');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
