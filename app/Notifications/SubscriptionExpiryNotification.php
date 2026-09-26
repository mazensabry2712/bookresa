<?php

namespace App\Notifications;

use App\Domain\Billing\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SubscriptionExpiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Subscription $subscription,
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
            'type' => 'subscription_expiring',
            'tenant_id' => $this->subscription->tenant_id,
            'subscription_id' => $this->subscription->getKey(),
            'plan' => $this->subscription->plan?->name,
            'ends_at' => $this->subscription->end_at?->toIso8601String(),
            'title' => [
                'en' => 'Subscription expiring soon',
                'ar' => 'الاشتراك سينتهي قريبًا',
            ],
            'message' => [
                'en' => 'Your BookResa subscription will expire within the next 24 hours.',
                'ar' => 'اشتراك BookResa الخاص بك سينتهي خلال الـ24 ساعة القادمة.',
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Subscription expiring soon')
            ->greeting('BookResa')
            ->line('Your subscription will expire within the next 24 hours.')
            ->line('Plan: '.($this->subscription->plan?->name['en'] ?? $this->subscription->plan?->name ?? 'Plan'))
            ->line('Ends: '.$this->subscription->end_at?->format('Y-m-d H:i').' UTC');
    }
}
