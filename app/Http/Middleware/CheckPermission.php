<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission): Response
    {
        $admin = auth()->guard('admin')->user();

        if (!$admin || !$admin->hasPermission($permission)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Bạn không có quyền thực hiện hành động này!'], 403);
            }
            return redirect()->route('admin.dashboard')->with('error', 'Bạn không có quyền truy cập tính năng này!');
        }

        return $next($request);
    }
}
