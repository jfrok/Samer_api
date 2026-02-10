<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $language;
    public $expiresMinutes = 10;

    /**
     * Create a new message instance.
     */
    public function __construct(string $otp, string $language = 'ar')
    {
        $this->otp = $otp;
        $this->language = $language;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->language === 'ar' ? 'رمز التحقق من متجر سامر' : 'Verification Code from Samer Store';
        return $this->subject($subject)
                    ->view('emails.otp')
                    ->with([
                        'otp' => $this->otp,
                        'language' => $this->language,
                        'expires' => $this->expiresMinutes,
                    ]);
    }
}
