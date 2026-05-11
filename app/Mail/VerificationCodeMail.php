<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code
    ) {}

    public function build(): self
    {
        return $this->subject('Mã xác thực tài khoản TicketRush')
            ->view('emails.verification_code')
            ->with(['code' => $this->code]);
    }
}
