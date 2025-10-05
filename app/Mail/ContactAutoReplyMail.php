<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactAutoReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $payload) {}

    public function build()
    {
        $appName = config('app.name', 'Tools Hub');

        return $this->subject("【{$appName}】お問い合わせありがとうございます")
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('emails.contact_autoreply_html', ['data' => $this->payload])
            ->text('emails.contact_autoreply_plain', ['data' => $this->payload])
            ->withSymfonyMessage(function (\Symfony\Component\Mime\Email $message) {
                $headers = $message->getHeaders();
                // 自動返信であることを各社MTAに明示
                $headers->addTextHeader('Auto-Submitted', 'auto-replied');
                // Microsoft/Google等の自動応答抑制用
                $headers->addTextHeader('X-Auto-Response-Suppress', 'All, OOF, DR, RN, NRN, AutoReply');
                // 互換用（古いMTA向け、任意）
                $headers->addTextHeader('Precedence', 'auto_reply');
            });
        }
}
