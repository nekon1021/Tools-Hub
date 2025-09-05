<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if (! $request->expectsJson()) {
            // 管理配下のURLだけ管理ログインへ
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }
        }
          return Route::has('login') ? route('login') : route('admin.login');
    }
}
