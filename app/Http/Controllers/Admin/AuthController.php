<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\OtpCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\AdminOtpMail;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function forgotPasswordShow()
    {
        return view('admin.auth.forgot-password');
    }

    public function forgotPasswordSendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:100|exists:admins,email'
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'email.max' => 'Email không được vượt quá 100 ký tự.',
            'email.exists' => 'Email này không tồn tại trong hệ thống quản trị.'
        ]);

        $otp = rand(100000, 999999);
        
        OtpCode::updateOrCreate(
            ['email' => $request->email, 'type' => 'admin'],
            [
                'otp_code' => $otp,
                'expires_at' => now()->addMinutes(1)
            ]
        );

        // Gửi Email
        Mail::to($request->email)->send(new AdminOtpMail($otp, 'reset'));

        if ($request->ajax()) {
            return response()->json(['success' => "Mã OTP đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư."]);
        }

        return back()->with('success', "Mã OTP đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.")->withInput();
    }

    public function forgotPasswordReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:100|exists:admins,email',
            'otp' => 'required|digits:6',
            'password' => [
                'required',
                'confirmed',
                'max:20',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.max' => 'Email không được vượt quá 100 ký tự.',
            'otp.required' => 'Vui lòng nhập mã OTP.',
            'otp.digits' => 'Mã OTP phải có 6 chữ số.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.max' => 'Mật khẩu không được vượt quá 20 ký tự.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.letters' => 'Mật khẩu phải chứa ít nhất một chữ cái.',
            'password.mixed' => 'Mật khẩu phải chứa cả chữ hoa và chữ thường.',
            'password.numbers' => 'Mật khẩu phải chứa ít nhất một chữ số.',
            'password.symbols' => 'Mật khẩu phải chứa ít nhất một ký tự đặc biệt.',
        ]);

        $otpData = OtpCode::where('email', $request->email)
            ->where('type', 'admin')
            ->where('otp_code', $request->otp)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpData) {
            return back()->with('error', 'Mã OTP không chính xác hoặc đã hết hạn.')->withInput();
        }

        $admin = Admin::where('email', $request->email)->first();
        $admin->password = Hash::make($request->password);
        $admin->save();

        // Delete used OTP
        $otpData->delete();

        return redirect()->route('admin.login')->with('success', 'Đặt lại mật khẩu thành công! Vui lòng đăng nhập bằng mật khẩu mới.');
    }

    public function loginShow()
    {
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            if ($admin->status == 1) {
                return redirect()->route('admin.users.index');
            }
            // Nếu status không phải 1, ép đăng xuất
            Auth::guard('admin')->logout();
            session()->invalidate();
            session()->regenerateToken();
        }
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string|max:100',
            'password' => 'required|string|max:20',
        ], [
            'login.required' => 'Email hoặc Tên đăng nhập không được để trống.',
            'login.max' => 'Thông tin đăng nhập không được quá 100 ký tự.',
            'password.required' => 'Mật khẩu không được để trống.',
            'password.max' => 'Mật khẩu không được quá 20 ký tự.',
        ]);

        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        $credentials = [
            $loginType => $request->login,
            'password' => $request->password,
        ];

        if (Auth::guard('admin')->attempt($credentials, $request->has('remember'))) {
            $admin = Auth::guard('admin')->user();
            
            if ($admin->status == 0) {
                Auth::guard('admin')->logout();
                return back()->with('error', 'Tài khoản của bạn đang chờ quản trị viên phê duyệt.')->withInput();
            }

            if ($admin->status == 2) {
                Auth::guard('admin')->logout();
                return back()->with('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.')->withInput();
            }

            $request->session()->regenerate();
            return redirect()->intended(route('admin.users.index'))->with('success', 'Chào mừng quay trở lại!');
        }

        return back()->with('error', 'Thông tin đăng nhập không chính xác.')->withInput();
    }

    public function registerShow()
    {
        return view('admin.auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:20|unique:admins',
            'email' => 'required|string|email|max:100|unique:admins',
            'otp' => 'required|digits:6',
            'password' => [
                'required',
                'confirmed',
                'max:20',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],
        ], [
            'name.required' => 'Tên không được để trống.',
            'name.unique' => 'Tên này đã tồn tại.',
            'email.required' => 'Email không được để trống.',
            'email.unique' => 'Email này đã tồn tại.',
            'otp.required' => 'Vui lòng nhập mã OTP.',
            'otp.digits' => 'Mã OTP phải có 6 chữ số.',
            'password.required' => 'Mật khẩu không được để trống.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.letters' => 'Mật khẩu phải chứa ít nhất một chữ cái.',
            'password.mixed' => 'Mật khẩu phải chứa cả chữ hoa và chữ thường.',
            'password.numbers' => 'Mật khẩu phải chứa ít nhất một chữ số.',
            'password.symbols' => 'Mật khẩu phải chứa ít nhất một ký tự đặc biệt.',
            'password.max' => 'Mật khẩu không được vượt quá 20 ký tự.',
        ]);

        // Kiểm tra OTP
        $otpData = OtpCode::where('email', $request->email)
            ->where('type', 'admin')
            ->where('otp_code', $request->otp)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpData) {
            return back()->with('error', 'Mã OTP không chính xác hoặc đã hết hạn.')->withInput();
        }

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'avatar' => '/default_avatar.jpg',
            'email_verified_at' => now(),
            'status' => 0, // Chờ duyệt
        ]);

        $otpData->delete();

        return redirect()->route('admin.login')->with('success', 'Đăng ký thành công! Vui lòng chờ quản trị viên phê duyệt để đăng nhập.');
    }

    public function registerResendOtp(Request $request)
    {
        // Khi gửi OTP lần đầu hoặc gửi lại ở trang đăng ký
        $request->validate(['email' => 'required|email'], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
        ]);
        
        // Kiểm tra email đã tồn tại chưa (chỉ check khi đăng ký mới)
        if (Admin::where('email', $request->email)->exists()) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Email này đã được đăng ký.'], 422);
            }
            return back()->with('error', 'Email này đã được đăng ký.');
        }

        $otp = rand(100000, 999999);
        OtpCode::updateOrCreate(
            ['email' => $request->email, 'type' => 'admin'],
            ['otp_code' => $otp, 'expires_at' => now()->addMinutes(1)]
        );

        Mail::to($request->email)->send(new AdminOtpMail($otp, 'register'));

        if ($request->ajax()) {
            return response()->json(['success' => 'Mã OTP đã được gửi đến email của bạn.']);
        }

        return back()->with('success', 'Mã OTP đã được gửi đến email của bạn.');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login')->with('success', 'Đã đăng xuất thành công.');
    }


    public function changePasswordShow()
    {
        return view('admin.auth.change-password');
    }

    public function changePasswordSendOtp(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $otp = rand(100000, 999999);
        
        OtpCode::updateOrCreate(
            ['email' => $admin->email, 'type' => 'admin'],
            [
                'otp_code' => $otp,
                'expires_at' => now()->addMinutes(1)
            ]
        );

        // Gửi Email
        Mail::to($admin->email)->send(new AdminOtpMail($otp, 'change'));

        if ($request->ajax()) {
            return response()->json(['success' => "Mã OTP xác thực đã được gửi đến email: {$admin->email}"]);
        }

        return back()->with('success', "Mã OTP xác thực đã được gửi đến email: {$admin->email}");
    }

    public function changePasswordUpdate(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
            'current_password' => 'required',
            'password' => [
                'required',
                'confirmed',
                'max:20',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],
        ], [
            'otp.required' => 'Vui lòng nhập mã OTP.',
            'otp.digits' => 'Mã OTP phải có 6 chữ số.',
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
            'password.min' => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
            'password.letters' => 'Mật khẩu mới phải chứa ít nhất một chữ cái.',
            'password.mixed' => 'Mật khẩu mới phải chứa cả chữ hoa và chữ thường.',
            'password.numbers' => 'Mật khẩu mới phải chứa ít nhất một chữ số.',
            'password.symbols' => 'Mật khẩu mới phải chứa ít nhất một ký tự đặc biệt.',
            'password.max' => 'Mật khẩu mới không được vượt quá 20 ký tự.',
        ]);

        $admin = Auth::guard('admin')->user();

        // Verify OTP
        $otpData = OtpCode::where('email', $admin->email)
            ->where('type', 'admin')
            ->where('otp_code', $request->otp)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpData) {
            return back()->with('error', 'Mã OTP không chính xác hoặc đã hết hạn.');
        }

        if (!Hash::check($request->current_password, $admin->password)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không chính xác.']);
        }

        $admin->password = Hash::make($request->password);
        $admin->save();

        // Delete used OTP
        $otpData->delete();

        // Logout after password change
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Đổi mật khẩu thành công! Vui lòng đăng nhập lại với mật khẩu mới.');
    }
}
