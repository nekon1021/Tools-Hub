<?php

// app/Mail/ContactMail.php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $payload) {}

    public function build()
    {
        $subject = 'お問い合わせ: ' . ($this->payload['subject'] ?: '件名なし');
        return $this->subject($subject)
            ->view('emails.contact'); // 下のBlade
    }
}
