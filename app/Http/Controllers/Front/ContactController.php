<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Mail\ContactMail;
use App\Mail\ContactAutoReplyMail; // ★ 追加
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; // ★ 追加

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

        // ★ 件名サニタイズ（ヘッダ改行対策 & 長さ制限の最終防衛）
        $data['subject'] = Str::of($data['subject'] ?? '')
            ->replace(["\r", "\n"], ' ')
            ->limit(120)
            ->__toString();

        // 宛先は .env で管理（メール直書き不要）
        $to = config('mail.contact_to', env('MAIL_CONTACT_TO'));
        if (!$to) {
            return back()->withErrors(['message' => '現在お問い合わせを受け付けられません。'])->withInput();
        }

        try {
            // 管理者宛（同期送信）
            Mail::to($to)->send(new ContactMail($data));

            // ★ ここに自動返信（管理者宛が成功した後、returnの前）
            try {
                Mail::to($data['email'])->send(new ContactAutoReplyMail($data));
            } catch (\Throwable $e) {
                Log::warning('Contact auto-reply failed', [
                    'error' => $e->getMessage(),
                    'email' => $data['email'],
                ]);
                // 自動返信失敗はUX維持のため黙殺（フラッシュメッセージは成功のまま）
            }

        } catch (\Throwable $e) {
            Log::error('Contact admin mail failed', [
                'error' => $e->getMessage(),
                // PIIは最小限に（必要ならマスキング）
                'email_hash' => sha1($data['email']),
            ]);
            return back()->withErrors(['message' => '送信に失敗しました。時間をおいて再度お試しください。'])->withInput();
        }

        return back()->with('status', 'お問い合わせを受け付けました。ありがとうございます。');
    }
}
