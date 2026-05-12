<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Exception;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Admin::query()->where('id', '!=', auth()->guard('admin')->id());

        // Lọc theo trạng thái
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Lọc theo trạng thái xác thực email
        if ($request->has('verified') && $request->verified !== '') {
            if ($request->verified == '1') {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Tìm kiếm theo tên hoặc email
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%");
            });
        }

        $admins = $query->latest()->paginate(10)->withQueryString();
        
        return view('admin.admins.index', compact('admins'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('admin.admins.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:20|unique:admins',
            'email' => 'required|string|email|max:100|unique:admins',
            'password' => [
                'required',
                'string',
                'min:8',
                'max:20',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:0,1,2',
            'roles' => 'required|array',
        ], [
            'roles.required' => 'Vui lòng chọn ít nhất một vai trò.',
            'name.required' => 'Tên đăng nhập không được để trống.',
            'name.max' => 'Tên đăng nhập không được quá :max ký tự.',
            'name.unique' => 'Tên đăng nhập này đã tồn tại.',
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không đúng định dạng.',
            'email.max' => 'Email không được quá :max ký tự.',
            'email.unique' => 'Email này đã tồn tại.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
            'password.max' => 'Mật khẩu tối đa :max ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.letters' => 'Mật khẩu phải chứa ít nhất một chữ cái.',
            'password.mixed' => 'Mật khẩu phải chứa cả chữ hoa và chữ thường.',
            'password.numbers' => 'Mật khẩu phải chứa ít nhất một chữ số.',
            'password.symbols' => 'Mật khẩu phải chứa ít nhất một ký tự đặc biệt.',
            'avatar.image' => 'File tải lên phải là hình ảnh.',
            'avatar.mimes' => 'Ảnh đại diện chỉ chấp nhận định dạng: jpeg, png, jpg, gif.',
            'avatar.max' => 'Dung lượng ảnh không được quá 2MB.',
        ]);

        try {
            $avatarPath = '/default_avatar.jpg';
            
            if ($request->cropped_avatar) {
                // Xử lý ảnh đã cắt (Base64)
                $image_parts = explode(";base64,", $request->cropped_avatar);
                $image_type_aux = explode("image/", $image_parts[0]);
                $image_type = $image_type_aux[1];
                $image_base64 = base64_decode($image_parts[1]);
                $fileName = 'avatars/' . uniqid() . '.' . $image_type;
                Storage::disk('public')->put($fileName, $image_base64);
                $avatarPath = '/storage/' . $fileName;
            } elseif ($request->hasFile('avatar')) {
                $path = $request->file('avatar')->store('avatars', 'public');
                $avatarPath = '/storage/' . $path;
            }

            $admin = Admin::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'avatar' => $avatarPath,
                'email_verified_at' => now(),
                'status' => $request->status,
            ]);

            // Gán vai trò
            $admin->roles()->sync($request->roles);

            return redirect()->route('admin.admins.index')->with('success', 'Thêm Admin mới thành công!');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function edit(Admin $admin)
    {
        if ($admin->id == 1) {
            return redirect()->route('admin.admins.index')->with('error', 'Không thể chỉnh sửa Admin gốc!');
        }
        $roles = Role::all();
        $adminRoles = $admin->roles->pluck('id')->toArray();
        return view('admin.admins.edit', compact('admin', 'roles', 'adminRoles'));
    }

    public function update(Request $request, Admin $admin)
    {
        if ($admin->id == 1) {
            return redirect()->route('admin.admins.index')->with('error', 'Không thể chỉnh sửa Admin gốc!');
        }
        $request->validate([
            'name' => 'required|string|max:20|unique:admins,name,' . $admin->id,
            'password' => [
                'nullable',
                'string',
                'min:8',
                'max:20',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:0,1,2',
            'roles' => 'required|array',
        ], [
            'roles.required' => 'Vui lòng chọn ít nhất một vai trò.',
            'name.required' => 'Tên đăng nhập không được để trống.',
            'name.max' => 'Tên đăng nhập không được quá :max ký tự.',
            'name.unique' => 'Tên đăng nhập này đã tồn tại.',
            'avatar.image' => 'File tải lên phải là hình ảnh.',
            'avatar.mimes' => 'Ảnh đại diện chỉ chấp nhận định dạng: jpeg, png, jpg, gif.',
            'avatar.max' => 'Dung lượng ảnh không được quá 2MB.',
            'password.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
            'password.max' => 'Mật khẩu tối đa :max ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.letters' => 'Mật khẩu phải chứa ít nhất một chữ cái.',
            'password.mixed' => 'Mật khẩu phải chứa cả chữ hoa và chữ thường.',
            'password.numbers' => 'Mật khẩu phải chứa ít nhất một chữ số.',
            'password.symbols' => 'Mật khẩu phải chứa ít nhất một ký tự đặc biệt.',
        ]);

        try {
            if ($request->remove_avatar == "1") {
                if ($admin->avatar && str_contains($admin->avatar, '/storage/')) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $admin->avatar));
                }
                $admin->avatar = '/default_avatar.jpg';
            }

            if ($request->cropped_avatar) {
                if ($admin->avatar && str_contains($admin->avatar, '/storage/')) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $admin->avatar));
                }
                $image_parts = explode(";base64,", $request->cropped_avatar);
                $image_type_aux = explode("image/", $image_parts[0]);
                $image_type = $image_type_aux[1];
                $image_base64 = base64_decode($image_parts[1]);
                $fileName = 'avatars/' . uniqid() . '.' . $image_type;
                Storage::disk('public')->put($fileName, $image_base64);
                $admin->avatar = '/storage/' . $fileName;
            } elseif ($request->hasFile('avatar')) {
                if ($admin->avatar && str_contains($admin->avatar, '/storage/')) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $admin->avatar));
                }
                $path = $request->file('avatar')->store('avatars', 'public');
                $admin->avatar = '/storage/' . $path;
            }

            $admin->name = $request->name;
            $admin->status = $request->status;

            if ($request->password) {
                $admin->password = Hash::make($request->password);
            }

            // Gán vai trò
            $admin->roles()->sync($request->roles);

            $admin->save();

            return redirect()->route('admin.admins.index')->with('success', 'Cập nhật Admin thành công!');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function destroy(Admin $admin)
    {
        if ($admin->id == 1) {
            return redirect()->route('admin.admins.index')->with('error', 'Không thể xóa Admin gốc!');
        }
        try {
            if ($admin->avatar && str_contains($admin->avatar, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $admin->avatar));
            }
            $admin->delete();
            return redirect()->route('admin.admins.index')->with('success', 'Xóa Admin thành công!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Có lỗi xảy ra khi xóa: ' . $e->getMessage());
        }
    }

    /**
     * Hiển thị hồ sơ cá nhân của Admin đang đăng nhập
     */
    public function profile()
    {
        $admin = auth()->guard('admin')->user();
        return view('admin.admins.profile', compact('admin'));
    }

    /**
     * Cập nhật hồ sơ cá nhân của Admin đang đăng nhập
     */
    public function profileUpdate(Request $request)
    {
        $admin = Admin::find(auth()->guard('admin')->id());
        
        $request->validate([
            'name' => 'required|string|max:20|unique:admins,name,' . $admin->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'name.required' => 'Tên đăng nhập không được để trống.',
            'name.max' => 'Tên đăng nhập không được quá :max ký tự.',
            'name.unique' => 'Tên đăng nhập này đã tồn tại.',
            'avatar.image' => 'File tải lên phải là hình ảnh.',
            'avatar.mimes' => 'Ảnh đại diện chỉ chấp nhận định dạng: jpeg, png, jpg, gif.',
            'avatar.max' => 'Dung lượng ảnh không được quá 2MB.',
        ]);

        try {
            if ($request->remove_avatar == "1") {
                if ($admin->avatar && str_contains($admin->avatar, '/storage/')) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $admin->avatar));
                }
                $admin->avatar = '/default_avatar.jpg';
            }

            if ($request->cropped_avatar) {
                if ($admin->avatar && str_contains($admin->avatar, '/storage/')) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $admin->avatar));
                }
                $image_parts = explode(";base64,", $request->cropped_avatar);
                $image_type_aux = explode("image/", $image_parts[0]);
                $image_type = $image_type_aux[1];
                $image_base64 = base64_decode($image_parts[1]);
                $fileName = 'avatars/' . uniqid() . '.' . $image_type;
                Storage::disk('public')->put($fileName, $image_base64);
                $admin->avatar = '/storage/' . $fileName;
            } elseif ($request->hasFile('avatar')) {
                if ($admin->avatar && str_contains($admin->avatar, '/storage/')) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $admin->avatar));
                }
                $path = $request->file('avatar')->store('avatars', 'public');
                $admin->avatar = '/storage/' . $path;
            }

            $admin->name = $request->name;
            $admin->save();

            return redirect()->back()->with('success', 'Cập nhật hồ sơ thành công!');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, Admin $admin)
    {
        if ($admin->id == 1) {
            return response()->json(['success' => false, 'message' => 'Không thể đổi trạng thái Admin gốc!'], 403);
        }

        $request->validate([
            'status' => 'required|in:0,1,2'
        ]);

        $admin->status = $request->status;
        $admin->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công!'
        ]);
    }
}
