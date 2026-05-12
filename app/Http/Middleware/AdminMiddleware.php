<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->guard('admin')->check()) {
            return redirect()->route('admin.login')->with('error', 'Vui lòng đăng nhập để truy cập trang quản trị.');
        }

        $admin = auth()->guard('admin')->user();

        if ($admin->status == 0) {
            auth()->guard('admin')->logout();
            return redirect()->route('admin.login')->with('error', 'Tài khoản của bạn đang chờ quản trị viên phê duyệt.');
        }

        if ($admin->status == 2) {
            auth()->guard('admin')->logout();
            return redirect()->route('admin.login')->with('error', 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.');
        }

        return $next($request);
    }
}
