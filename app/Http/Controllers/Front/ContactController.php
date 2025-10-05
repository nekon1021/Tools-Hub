<?php

// app/Http/Controllers/Front/ContactController.php
namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Mail\ContactMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function show(Request $request)
    {
        return view('public.pages.contact', [
            'ts' => now()->timestamp,
        ]);
    }

    public function send(Request $request)
    {
        // --- 簡易スパム対策（ハニーポット+表示から送信までの最短時間）
        if (!empty($request->input('website'))) {
            return back()->with('status', '送信を受け付けました。');
        }
        $started = (int)$request->input('_started_at');
        if (!$started || (time() - $started) < 3) {
            return back()->withErrors(['message' => '送信に失敗しました。もう一度お試しください。'])->withInput();
        }

        $data = $request->validate([
            'name'    => ['required','string','max:80'],
            'email'   => ['required','email:rfc,dns','max:190'],
            'subject' => ['nullable','string','max:120'],
            'message' => ['required','string','max:2000'],
        ]);

        // 宛先は .env で管理（メール直書き不要）
        $to = config('mail.contact_to', env('MAIL_CONTACT_TO'));
        if (!$to) {
            return back()->withErrors(['message' => '現在お問い合わせを受け付けられません。'])->withInput();
        }

        try {
            Mail::to($to)->send(new ContactMail($data)); // 同期送信
        } catch (\Throwable $e) {
            Log::error('Contact admin mail failed', [
                'error' => $e->getMessage(),
                'data'  => $data,
            ]);
            return back()->withErrors(['message' => '送信に失敗しました。時間をおいて再度お試しください。'])->withInput();
        }

        return back()->with('status', 'お問い合わせを受け付けました。ありがとうございます。');
    }
}
