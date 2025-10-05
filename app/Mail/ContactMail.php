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
        $subject = '【Tools Hub】お問い合わせ: ' . ($this->payload['subject'] ?: '件名なし');
        
        return $this->subject($subject)
            ->replyTo($this->payload['email'])
            ->text('emails.contact_received_plain')
            ->with(['data' => $this->payload]);
    }
}
