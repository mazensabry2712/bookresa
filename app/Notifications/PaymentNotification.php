<?php

namespace App\Notifications;

use App\Domain\Payment\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Payment $payment,
        private readonly string $kind,
    ) {
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
        $content = $this->content();

        return [
            'type' => 'payment_'.$this->kind,
            'tenant_id' => $this->payment->tenant_id,
            'payment_id' => $this->payment->getKey(),
            'payment_reference' => $this->payment->reference,
            'amount_minor' => $this->payment->amount_minor,
            'currency' => $this->payment->currency,
            'title' => [
                'en' => $content['title_en'],
                'ar' => $content['title_ar'],
            ],
            'message' => [
                'en' => $content['message_en'],
                'ar' => $content['message_ar'],
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $content = $this->content();

        return (new MailMessage)
            ->subject($content['title_en'].' · '.$this->payment->reference)
            ->greeting('BookResa')
            ->line($content['message_en'])
            ->line('Payment: '.$this->payment->reference)
            ->line('Amount: '.$this->payment->amount_minor.' '.$this->payment->currency);
    }

    private function content(): array
    {
        return match ($this->kind) {
            'paid' => [
                'title_en' => 'Payment received',
                'title_ar' => 'تم استلام الدفع',
                'message_en' => 'Your payment was received successfully.',
                'message_ar' => 'تم استلام دفعتك بنجاح.',
            ],
            'failed' => [
                'title_en' => 'Payment failed',
                'title_ar' => 'فشل الدفع',
                'message_en' => 'Your payment could not be completed.',
                'message_ar' => 'تعذر إتمام دفعتك.',
            ],
            default => throw new \InvalidArgumentException('Unsupported payment notification kind.'),
        };
    }
}
