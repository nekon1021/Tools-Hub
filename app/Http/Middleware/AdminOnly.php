<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ログイン画面自体にこのミドルウェアを掛けているとループするので通す
        if ($request->routeIs('admin.login')) {
            return $next($request);
        }

        $user = $request->user(); // webガード前提

        // 未認証
        if (!$user) {
            // API/JSON は 401、画面はログインへ
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('admin.login');
        }

        // 認証済だが管理者でない
        if (!($user->is_admin ?? false)) {
            // API/JSON は 403、画面は 404 偽装（好みで 403 でも可）
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
            abort(404);
        }

        return $next($request);
    }
}
