<?php

namespace App\Notifications;

use App\Domain\Booking\Models\Booking;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BookingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Booking $booking,
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
            'type' => 'booking_'.$this->kind,
            'tenant_id' => $this->booking->tenant_id,
            'booking_id' => $this->booking->getKey(),
            'booking_reference' => $this->booking->booking_reference,
            'service' => $this->booking->service?->name,
            'starts_at' => $this->booking->starts_at?->toIso8601String(),
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
            ->subject($content['title_en'].' · '.$this->booking->booking_reference)
            ->greeting('BookResa')
            ->line($content['message_en'])
            ->line('Booking: '.$this->booking->booking_reference)
            ->line('Service: '.($this->booking->service?->name['en'] ?? $this->booking->service?->name ?? 'Service'))
            ->line('Starts: '.$this->booking->starts_at?->setTimezone($this->timezone())->format('Y-m-d H:i'));
    }

    private function content(): array
    {
        return match ($this->kind) {
            'confirmed' => [
                'title_en' => 'Booking confirmed',
                'title_ar' => 'تم تأكيد الحجز',
                'message_en' => 'Your booking has been confirmed.',
                'message_ar' => 'تم تأكيد حجزك.',
            ],
            'cancelled' => [
                'title_en' => 'Booking cancelled',
                'title_ar' => 'تم إلغاء الحجز',
                'message_en' => 'Your booking has been cancelled.',
                'message_ar' => 'تم إلغاء حجزك.',
            ],
            'rescheduled' => [
                'title_en' => 'Booking rescheduled',
                'title_ar' => 'تم إعادة جدولة الحجز',
                'message_en' => 'Your booking time has been changed.',
                'message_ar' => 'تم تغيير موعد حجزك.',
            ],
            'created' => [
                'title_en' => 'Booking received',
                'title_ar' => 'تم استلام الحجز',
                'message_en' => 'Your booking request was received and is pending confirmation.',
                'message_ar' => 'تم استلام طلب حجزك وهو في انتظار التأكيد.',
            ],
            'reminder' => [
                'title_en' => 'Booking reminder',
                'title_ar' => 'تذكير بالحجز',
                'message_en' => 'Your booking is coming up soon.',
                'message_ar' => 'موعد حجزك اقترب.',
            ],
            default => throw new \InvalidArgumentException('Unsupported booking notification kind.'),
        };
    }

    private function timezone(): string
    {
        return Tenant::query()
            ->with('profile')
            ->find($this->booking->tenant_id)?->profile?->timezone
            ?? config('app.timezone', 'UTC');
    }
}
