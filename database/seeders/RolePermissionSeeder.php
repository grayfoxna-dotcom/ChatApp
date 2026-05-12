<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Tạo các Quyền hạn (Permissions)
        $permissions = [
            // Nhóm Người dùng
            ['name' => 'users.view', 'display_name' => 'Xem danh sách người dùng', 'group' => 'Người dùng'],
            ['name' => 'users.create', 'display_name' => 'Thêm người dùng', 'group' => 'Người dùng'],
            ['name' => 'users.edit', 'display_name' => 'Sửa người dùng', 'group' => 'Người dùng'],
            ['name' => 'users.delete', 'display_name' => 'Xóa người dùng', 'group' => 'Người dùng'],

            // Nhóm Admin
            ['name' => 'admins.view', 'display_name' => 'Xem danh sách Admin', 'group' => 'Admin'],
            ['name' => 'admins.create', 'display_name' => 'Thêm Admin', 'group' => 'Admin'],
            ['name' => 'admins.edit', 'display_name' => 'Sửa Admin', 'group' => 'Admin'],
            ['name' => 'admins.delete', 'display_name' => 'Xóa Admin', 'group' => 'Admin'],

            // Nhóm Phân quyền
            ['name' => 'roles.view', 'display_name' => 'Xem danh sách vai trò', 'group' => 'Hệ thống'],
            ['name' => 'roles.create', 'display_name' => 'Thêm vai trò', 'group' => 'Hệ thống'],
            ['name' => 'roles.edit', 'display_name' => 'Sửa vai trò', 'group' => 'Hệ thống'],
            ['name' => 'roles.delete', 'display_name' => 'Xóa vai trò', 'group' => 'Hệ thống'],
        ];

        foreach ($permissions as $p) {
            \App\Models\Permission::updateOrCreate(['name' => $p['name']], $p);
        }

        // 2. Tạo các Vai trò (Roles)
        $superAdmin = \App\Models\Role::updateOrCreate(
            ['name' => 'super_admin'],
            ['display_name' => 'Quản trị tối cao', 'description' => 'Toàn quyền hệ thống']
        );

        $manager = \App\Models\Role::updateOrCreate(
            ['name' => 'manager'],
            ['display_name' => 'Quản lý', 'description' => 'Quản lý nhân sự và người dùng']
        );

        $editor = \App\Models\Role::updateOrCreate(
            ['name' => 'editor'],
            ['display_name' => 'Biên tập viên', 'description' => 'Chỉ được xem và sửa thông tin cơ bản']
        );

        // 3. Gán quyền cho vai trò
        // Super Admin lấy tất cả quyền
        $superAdmin->permissions()->sync(\App\Models\Permission::all());

        // Manager lấy quyền về user và admin (trừ xóa admin và quản lý role)
        $managerPermissions = \App\Models\Permission::where('group', '!=', 'Hệ thống')
            ->where('name', '!=', 'admins.delete')
            ->get();
        $manager->permissions()->sync($managerPermissions);

        // Editor chỉ xem
        $editorPermissions = \App\Models\Permission::where('name', 'like', '%.view')->get();
        $editor->permissions()->sync($editorPermissions);
    }
}
