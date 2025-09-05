<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $cred = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($cred, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // 管理者以外は即締め出す（存在秘匿）
            if (!auth()->user()->is_admin) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                abort(404);
            }
            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors(['email' => '認証に失敗しました'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        // 存在チェックしつつ安全にリダイレクト
        $dest = Route::has('admin.login')
            ? route('admin.login')
            : (Route::has('tools.index') ? route('tools.index') : url('/'));

        return redirect()->to($dest);
    }
}
