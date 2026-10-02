<?php

namespace App\Notifications;

use App\Domain\Support\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SupportReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly SupportTicket $ticket,
        private readonly string $message,
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
        return [
            'type' => 'support_reply',
            'tenant_id' => $this->ticket->tenant_id,
            'ticket_id' => $this->ticket->getKey(),
            'title' => [
                'en' => 'New BookResa support reply',
                'ar' => 'لديك رد جديد من دعم BookResa',
            ],
            'message' => [
                'en' => $this->message,
                'ar' => $this->message,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('BookResa support reply · #'.$this->ticket->getKey())
            ->greeting('BookResa Support')
            ->line($this->message)
            ->line('Ticket: #'.$this->ticket->getKey().' · '.$this->ticket->subject);
    }
}
