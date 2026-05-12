<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Exception;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // Lọc theo trạng thái hoạt động
        if ($request->has('isActive') && $request->isActive !== '') {
            $query->where('isActive', $request->isActive);
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

        $users = $query->latest()->paginate(10)->withQueryString();
        
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:20|unique:users',
            'email' => 'required|string|email|max:100|unique:users',
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
            'isActive' => 'required|in:0,1',
        ], [
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
                // Xử lý file upload bình thường nếu không có ảnh đã cắt
                $path = $request->file('avatar')->store('avatars', 'public');
                $avatarPath = '/storage/' . $path;
            }

            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'avatar' => $avatarPath,
                'isActive' => $request->isActive,
                'email_verified_at' => now(),
            ]);

            return redirect()->route('admin.users.index')->with('success', 'Thêm người dùng thành công!');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:20|unique:users,name,' . $user->id,
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
            'isActive' => 'required|in:0,1',
        ], [
            'name.required' => 'Tên đăng nhập không được để trống.',
            'name.max' => 'Tên đăng nhập không được quá :max ký tự.',
            'name.unique' => 'Tên đăng nhập này đã tồn tại.',
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
            if ($request->remove_avatar == "1") {
                // Xóa avatar cũ nếu có và reset về mặc định
                if ($user->avatar && str_contains($user->avatar, '/storage/')) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $user->avatar));
                }
                $user->avatar = '/default_avatar.jpg';
            }

            if ($request->cropped_avatar) {
                // Xử lý ảnh đã cắt mới (Base64)
                if ($user->avatar && str_contains($user->avatar, '/storage/')) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $user->avatar));
                }
                $image_parts = explode(";base64,", $request->cropped_avatar);
                $image_type_aux = explode("image/", $image_parts[0]);
                $image_type = $image_type_aux[1];
                $image_base64 = base64_decode($image_parts[1]);
                $fileName = 'avatars/' . uniqid() . '.' . $image_type;
                Storage::disk('public')->put($fileName, $image_base64);
                $user->avatar = '/storage/' . $fileName;
            } elseif ($request->hasFile('avatar')) {
                // Xóa avatar cũ nếu có và cập nhật ảnh mới (file upload)
                if ($user->avatar && str_contains($user->avatar, '/storage/')) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $user->avatar));
                }
                $path = $request->file('avatar')->store('avatars', 'public');
                $user->avatar = '/storage/' . $path;
            }

            $user->name = $request->name;
            $user->isActive = $request->isActive;

            if ($request->password) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            return redirect()->route('admin.users.index')->with('success', 'Cập nhật người dùng thành công!');
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function destroy(User $user)
    {
        try {
            // Xóa avatar khi xóa user
            if ($user->avatar && str_contains($user->avatar, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $user->avatar));
            }
            $user->delete();
            return redirect()->route('admin.users.index')->with('success', 'Xóa người dùng thành công!');
        } catch (Exception $e) {
            return redirect()->route('admin.users.index')->with('error', 'Không thể xóa người dùng: ' . $e->getMessage());
        }
    }

    public function updateActive(Request $request, User $user)
    {
        $request->validate([
            'isActive' => 'required|in:0,1'
        ]);

        $user->isActive = $request->isActive;
        $user->save();

        // Nếu khóa tài khoản, xóa tất cả access tokens để đăng xuất người dùng
        if ($user->isActive == 0) {
            $user->tokens()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công!'
        ]);
    }
}
