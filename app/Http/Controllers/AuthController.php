<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\OtpCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\VerifyOtpMail;

class AuthController extends Controller
{
    /**
     * Heartbeat để cập nhật thời gian hoạt động cuối cùng
     */
    public function heartbeat(Request $request)
    {
        $request->user()->update(['last_seen_at' => now()]);

        return response()->json(['status_response' => 'success']);
    }
    /**
     * Đăng ký người dùng mới
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:10',
            'last_name' => 'required|string|max:10',
            'email' => 'required|string|email|max:100',
            'password' => [
                'required',
                'string',
                'min:8',
                'max:20',
                \Illuminate\Validation\Rules\Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ], [
            'first_name.required' => 'Vui lòng nhập Tên',
            'first_name.max' => 'Tên tối đa 10 ký tự',
            'last_name.required' => 'Vui lòng nhập Họ',
            'last_name.max' => 'Họ tối đa 10 ký tự',
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không đúng định dạng',
            'email.max' => 'Email tối đa 100 ký tự',
            'password.required' => 'Vui lòng nhập mật khẩu',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
            'password.max' => 'Mật khẩu tối đa 20 ký tự',
            'password.letters' => 'Mật khẩu phải chứa ít nhất một chữ cái',
            'password.mixed' => 'Mật khẩu phải chứa cả chữ hoa và chữ thường',
            'password.numbers' => 'Mật khẩu phải chứa ít nhất một chữ số',
            'password.symbols' => 'Mật khẩu phải chứa ít nhất một ký tự đặc biệt',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => $validator->errors()->first(),
            ]);
        }

        // Gộp Tên và Họ (Tên trước họ sau)
        $name = trim($request->first_name . ' ' . $request->last_name);

        // 1. Kiểm tra Email đã tồn tại và xác thực chưa
        $userByEmail = User::where('email', $request->email)->first();
        if ($userByEmail && $userByEmail->email_verified_at !== null) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Email này đã được sử dụng.',
            ]);
        }

        // 2. Kiểm tra Tên (sau khi gộp) đã tồn tại và xác thực chưa
        $userByName = User::where('name', $name)->first();
        if ($userByName && $userByName->email_verified_at !== null) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Tên người dùng này đã tồn tại.',
            ]);
        }

        // 3. Xử lý Đăng ký
        $user = $userByEmail ?? new User();

        $user->name = $name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->avatar = '/default_avatar.jpg';
        $user->isActive = 1;
        $user->save();

        // 4. Tạo mã OTP (6 số ngẫu nhiên)
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Lưu vào bảng otp_codes (Cập nhật hoặc chèn mới)
        OtpCode::updateOrInsert(
            ['email' => $request->email, 'type' => 'user'],
            [
                'otp_code' => $otpCode,
                'expires_at' => now()->addMinutes(1), // Hết hạn sau 1 phút
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 5. Gửi Mail
        Mail::to($request->email)->send(new VerifyOtpMail($otpCode));

        return response()->json([
            'status_response' => 'success',
            'message_response' => 'Đăng ký thành công. Vui lòng kiểm tra email để lấy mã xác thực.',
        ]);
    }

    /**
     * Đăng nhập người dùng
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|string|max:20',
        ], [
            'login.required' => 'Vui lòng nhập email hoặc tên tài khoản',
            'password.required' => 'Vui lòng nhập mật khẩu',
            'password.max' => 'Mật khẩu tối đa 20 ký tự',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => $validator->errors()->first(),
            ]);
        }

        // Tìm user bằng email hoặc tên tài khoản (name)
        $user = User::where('email', $request->login)
            ->orWhere('name', $request->login)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Thông tin đăng nhập không chính xác'
            ]);
        }

        // Kiểm tra tài khoản có bị khóa không
        if ($user->isActive == 0) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.'
            ]);
        }

        // Xóa tất cả các token cũ trước khi tạo token mới
        $user->tokens()->delete(); 

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status_response' => 'success',
            'message_response' => 'Đăng nhập thành công',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    /**
     * Lấy thông tin user hiện tại
     */
    public function profile(Request $request)
    {
        return response()->json([
            'status_response' => 'success',
            'data' => $request->user()
        ]);
    }

    /**
     * Xác thực mã OTP
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:100',
            'otp_code' => 'required|string|size:6',
        ], [
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không đúng định dạng',
            'otp_code.required' => 'Vui lòng nhập mã OTP',
            'otp_code.size' => 'Mã OTP phải có đúng 6 chữ số',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => $validator->errors()->first(),
            ]);
        }

        // Kiểm tra mã OTP trong bảng otp_codes
        $otpEntry = OtpCode::where('email', $request->email)
            ->where('type', 'user')
            ->where('otp_code', $request->otp_code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpEntry) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Mã xác thực không chính xác hoặc đã hết hạn.',
            ]);
        }

        // Xác thực người dùng
        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->email_verified_at = now();
            $user->save();

            // Xóa mã OTP sau khi xác thực thành công
            $otpEntry->delete();

            return response()->json([
                'status_response' => 'success',
                'message_response' => 'Xác thực tài khoản thành công.',
            ]);
        }

        return response()->json([
            'status_response' => 'error',
            'message_response' => 'Không tìm thấy người dùng.',
        ]);
    }

    /**
     * Gửi lại mã OTP
     */
    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:100',
        ], [
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không đúng định dạng',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => $validator->errors()->first(),
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Email không tồn tại trong hệ thống.',
            ]);
        }

        // Bỏ kiểm tra verified để dùng chung cho cả đổi mật khẩu

        // Tạo mã OTP mới (6 số ngẫu nhiên)
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Cập nhật hoặc chèn mới vào bảng otp_codes
        OtpCode::updateOrInsert(
            ['email' => $request->email, 'type' => 'user'],
            [
                'otp_code' => $otpCode,
                'expires_at' => now()->addMinutes(1),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Gửi Mail
        Mail::to($request->email)->send(new VerifyOtpMail($otpCode));

        return response()->json([
            'status_response' => 'success',
            'message_response' => 'Mã xác thực mới đã được gửi vào email của bạn.',
        ]);
    }

    /**
     * Đổi mật khẩu bằng mã OTP
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:100',
            'otp_code' => 'required|string|size:6',
            'password' => [
                'required',
                'string',
                'min:8',
                'max:20',
                \Illuminate\Validation\Rules\Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ], [
            'email.required' => 'Vui lòng nhập email',
            'email.email' => 'Email không đúng định dạng',
            'otp_code.required' => 'Vui lòng nhập mã OTP',
            'otp_code.size' => 'Mã OTP phải có đúng 6 chữ số',
            'password.required' => 'Vui lòng nhập mật khẩu mới',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
            'password.max' => 'Mật khẩu tối đa 20 ký tự',
            'password.letters' => 'Mật khẩu phải chứa ít nhất một chữ cái',
            'password.mixed' => 'Mật khẩu phải chứa cả chữ hoa và chữ thường',
            'password.numbers' => 'Mật khẩu phải chứa ít nhất một chữ số',
            'password.symbols' => 'Mật khẩu phải chứa ít nhất một ký tự đặc biệt',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => $validator->errors()->first(),
            ]);
        }

        // 1. Kiểm tra mã OTP
        $otpEntry = OtpCode::where('email', $request->email)
            ->where('type', 'user')
            ->where('otp_code', $request->otp_code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpEntry) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Mã xác thực không chính xác hoặc đã hết hạn.',
            ]);
        }

        // 2. Cập nhật mật khẩu
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Không tìm thấy người dùng.',
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // 3. Xóa OTP và các token cũ để yêu cầu đăng nhập lại
        $otpEntry->delete();
        $user->tokens()->delete();

        return response()->json([
            'status_response' => 'success',
            'message_response' => 'Đổi mật khẩu thành công. Vui lòng đăng nhập lại.',
        ]);
    }
    /**
     * Đổi mật khẩu cho người dùng đang đăng nhập
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp_code' => 'required|string|size:6',
            'current_password' => 'required|string',
            'password' => [
                'required',
                'string',
                'min:8',
                'max:20',
                'confirmed',
                \Illuminate\Validation\Rules\Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ], [
            'otp_code.required' => 'Vui lòng nhập mã OTP',
            'otp_code.size' => 'Mã OTP phải có đúng 6 chữ số',
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại',
            'password.required' => 'Vui lòng nhập mật khẩu mới',
            'password.confirmed' => 'Xác nhận mật khẩu mới không khớp',
            'password.min' => 'Mật khẩu mới phải có ít nhất 8 ký tự',
            'password.max' => 'Mật khẩu mới tối đa 20 ký tự',
            'password.letters' => 'Mật khẩu mới phải chứa ít nhất một chữ cái',
            'password.mixed' => 'Mật khẩu mới phải chứa cả chữ hoa và chữ thường',
            'password.numbers' => 'Mật khẩu mới phải chứa ít nhất một chữ số',
            'password.symbols' => 'Mật khẩu mới phải chứa ít nhất một ký tự đặc biệt',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => $validator->errors()->first(),
            ]);
        }

        $user = $request->user();

        // 1. Kiểm tra mã OTP
        $otpEntry = OtpCode::where('email', $user->email)
            ->where('type', 'user')
            ->where('otp_code', $request->otp_code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpEntry) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Mã xác thực không chính xác hoặc đã hết hạn.',
            ]);
        }

        // 2. Kiểm tra mật khẩu hiện tại
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => 'Mật khẩu hiện tại không chính xác.',
            ]);
        }

        // 3. Cập nhật mật khẩu mới
        $user->password = Hash::make($request->password);
        $user->save();

        // 4. Xóa OTP và các token cũ
        $otpEntry->delete();
        $user->tokens()->delete();

        return response()->json([
            'status_response' => 'success',
            'message_response' => 'Đổi mật khẩu thành công. Vui lòng đăng nhập lại.',
        ]);
    }

    /**
     * Cập nhật thông tin cá nhân
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:20|unique:users,name,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'name.required' => 'Vui lòng nhập tên hiển thị',
            'name.max' => 'Tên hiển thị tối đa 20 ký tự',
            'name.unique' => 'Tên hiển thị này đã tồn tại',
            'avatar.image' => 'File tải lên phải là hình ảnh',
            'avatar.mimes' => 'Ảnh đại diện chỉ chấp nhận định dạng: jpeg, png, jpg, gif',
            'avatar.max' => 'Dung lượng ảnh không được quá 2MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_response' => 'error',
                'message_response' => $validator->errors()->first(),
            ]);
        }

        // 1. Cập nhật tên
        $user->name = $request->name;

        // 2. Cập nhật ảnh đại diện (nếu có)
        if ($request->hasFile('avatar')) {
            // Xóa ảnh cũ nếu có
            if ($user->avatar && str_contains($user->avatar, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $user->avatar));
            }
            
            // Lưu ảnh mới
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = asset('storage/' . $path);
        }

        $user->save();

        return response()->json([
            'status_response' => 'success',
            'message_response' => 'Cập nhật thông tin thành công!',
            'data' => [
                'user' => $user
            ]
        ]);
    }
}
