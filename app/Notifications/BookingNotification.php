<?php

namespace App\Notifications;

use App\Domain\Booking\Models\Booking;
use App\Domain\Service\Models\Service;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BookingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @var array<string, string> */
    private readonly array $serviceName;

    private readonly ?string $startsAt;

    private readonly string $timezone;

    public function __construct(
        private readonly Booking $booking,
        private readonly string $kind,
    ) {
        $this->serviceName = $this->resolveServiceName();
        $this->startsAt = $booking->starts_at?->toIso8601String();
        $this->timezone = Tenant::query()
            ->with('profile')
            ->find($booking->tenant_id)?->profile?->timezone
            ?? config('app.timezone', 'UTC');

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
            'service' => $this->serviceName,
            'starts_at' => $this->startsAt,
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
            ->line('Service: '.($this->serviceName['en'] ?? $this->serviceName['ar'] ?? 'Service'))
            ->line('Starts: '.($this->startsAt === null
                ? 'N/A'
                : \Carbon\CarbonImmutable::parse($this->startsAt)->setTimezone($this->timezone)->format('Y-m-d H:i')));
    }

    private function content(): array
    {
        return match ($this->kind) {
            'created' => [
                'title_en' => 'Booking received',
                'title_ar' => 'تم استلام الحجز',
                'message_en' => 'Your booking request was received and is pending confirmation.',
                'message_ar' => 'تم استلام طلب حجزك وهو في انتظار التأكيد.',
            ],
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
            'reminder' => [
                'title_en' => 'Booking reminder',
                'title_ar' => 'تذكير بالحجز',
                'message_en' => 'Your booking is coming up soon.',
                'message_ar' => 'موعد حجزك اقترب.',
            ],
            default => throw new \InvalidArgumentException('Unsupported booking notification kind.'),
        };
    }

    /** @return array<string, string> */
    private function resolveServiceName(): array
    {
        $service = $this->booking->relationLoaded('service')
            ? $this->booking->service
            : Service::withoutGlobalScopes()->find($this->booking->service_id);

        $name = $service?->name;

        if (is_array($name)) {
            return array_filter([
                'en' => (string) ($name['en'] ?? ''),
                'ar' => (string) ($name['ar'] ?? ''),
            ]);
        }

        return ['en' => (string) ($name ?? 'Service')];
    }
}
