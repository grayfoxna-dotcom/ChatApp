<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Exception;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('admins')->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::all()->groupBy('group');
        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'display_name' => 'required|string',
            'permissions' => 'required|array'
        ], [
            'name.required' => 'Mã vai trò không được để trống.',
            'name.unique' => 'Mã vai trò này đã tồn tại.',
            'display_name.required' => 'Tên hiển thị không được để trống.',
            'permissions.required' => 'Vui lòng chọn ít nhất một quyền.'
        ]);

        try {
            $role = Role::create([
                'name' => $request->name,
                'display_name' => $request->display_name,
                'description' => $request->description,
            ]);

            $role->permissions()->sync($request->permissions);

            return redirect()->route('admin.roles.index')->with('success', 'Thêm vai trò thành công!');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function edit(Role $role)
    {
        if ($role->name === 'super_admin') {
            return redirect()->route('admin.roles.index')->with('error', 'Không thể chỉnh sửa vai trò Quản trị tối cao!');
        }
        $permissions = Permission::all()->groupBy('group');
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        if ($role->name === 'super_admin') {
            return redirect()->route('admin.roles.index')->with('error', 'Không thể chỉnh sửa vai trò Quản trị tối cao!');
        }

        $request->validate([
            'display_name' => 'required|string',
            'permissions' => 'required|array'
        ]);

        try {
            $role->update([
                'display_name' => $request->display_name,
                'description' => $request->description,
            ]);

            $role->permissions()->sync($request->permissions);

            return redirect()->route('admin.roles.index')->with('success', 'Cập nhật vai trò thành công!');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'super_admin') {
            return redirect()->route('admin.roles.index')->with('error', 'Không thể xóa vai trò hệ thống!');
        }

        if ($role->admins()->count() > 0) {
            return redirect()->route('admin.roles.index')->with('error', 'Không thể xóa vai trò đang có Admin sử dụng!');
        }

        $role->delete();
        return redirect()->route('admin.roles.index')->with('success', 'Xóa vai trò thành công!');
    }
}
