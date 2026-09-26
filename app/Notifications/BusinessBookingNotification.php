<?php

namespace App\Notifications;

use App\Domain\Booking\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BusinessBookingNotification extends Notification implements ShouldQueue
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
        $created = $this->kind === 'created';

        return [
            'type' => 'business_booking_'.$this->kind,
            'tenant_id' => $this->booking->tenant_id,
            'booking_id' => $this->booking->getKey(),
            'booking_reference' => $this->booking->booking_reference,
            'customer_id' => $this->booking->customer_id,
            'title' => [
                'en' => $created ? 'New booking' : 'Booking cancelled',
                'ar' => $created ? 'حجز جديد' : 'تم إلغاء الحجز',
            ],
            'message' => [
                'en' => $created
                    ? 'A new customer booking is waiting for review.'
                    : 'A booking in your workspace has been cancelled.',
                'ar' => $created
                    ? 'يوجد حجز جديد من عميل في انتظار المراجعة.'
                    : 'تم إلغاء حجز في مساحة نشاطك.',
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject($data['title']['en'].' · '.$this->booking->booking_reference)
            ->greeting('BookResa')
            ->line($data['message']['en'])
            ->line('Booking: '.$this->booking->booking_reference)
            ->line('Customer: '.($this->booking->customer->name ?? 'Customer'));
    }
}
