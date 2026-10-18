<?php

namespace App\Notifications\Auth;

use App\Domain\Identity\Services\EmailVerificationOtpService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class EmailVerificationOtpNotification extends Notification
{
    public function __construct(public readonly string $code)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('app.verify_email_subject'))
            ->view([
                'emails.auth.verify-email-otp',
                'emails.auth.verify-email-otp-text',
            ], [
                'user' => $notifiable,
                'code' => $this->code,
                'expiresMinutes' => EmailVerificationOtpService::OTP_TTL_MINUTES,
            ]);
    }
}
