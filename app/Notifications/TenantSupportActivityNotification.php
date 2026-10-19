<?php

namespace App\Notifications;

use App\Domain\Support\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TenantSupportActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  'created'|'reply'  $activity
     */
    public function __construct(
        private readonly SupportTicket $ticket,
        private readonly string $message,
        private readonly string $activity,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isNewTicket = $this->activity === 'created';
        $tenant = $this->ticket->tenant;

        return (new MailMessage)
            ->subject(($isNewTicket ? 'New support ticket #' : 'New workspace reply on ticket #')
                .$this->ticket->getKey().' · BookResa')
            ->greeting('BookResa Support')
            ->line('Workspace: '.($tenant?->slug ?? 'Unknown workspace'))
            ->line('Ticket: #'.$this->ticket->getKey())
            ->line('Subject: '.$this->ticket->subject)
            ->line('Priority: '.str($this->ticket->priority->value)->headline())
            ->line($isNewTicket ? 'A new support ticket was submitted.' : 'A workspace member replied to a support ticket.')
            ->line('Message:')
            ->line($this->message)
            ->action('Open support ticket', route('admin.support.show', $this->ticket->getKey()));
    }
}
