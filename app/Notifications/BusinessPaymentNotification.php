<?php

namespace App\Notifications;

use App\Domain\Payment\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BusinessPaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Payment $payment)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return filled($notifiable->routeNotificationFor('mail'))
            ? ['database', 'mail']
            : ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'business_payment_paid',
            'tenant_id' => $this->payment->tenant_id,
            'payment_id' => $this->payment->getKey(),
            'payment_reference' => $this->payment->reference,
            'amount_minor' => $this->payment->amount_minor,
            'currency' => $this->payment->currency,
            'title' => [
                'en' => 'Payment received',
                'ar' => 'تم استلام دفعة',
            ],
            'message' => [
                'en' => 'A customer payment was received for your business.',
                'ar' => 'تم استلام دفعة من أحد عملاء نشاطك.',
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject($data['title']['en'].' · '.$this->payment->reference)
            ->greeting('Velto')
            ->line($data['message']['en'])
            ->line('Payment: '.$this->payment->reference)
            ->line('Amount: '.number_format($this->payment->amount_minor / 100, 2).' '.$this->payment->currency);
    }
}
