<?php

namespace App\Notifications;

use App\Domain\Billing\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class UsageWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Subscription $subscription,
        private readonly int $customerCount,
        private readonly int $includedLimit,
        private readonly int $thresholdPercent,
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
            'type' => 'usage_warning',
            'tenant_id' => $this->subscription->tenant_id,
            'subscription_id' => $this->subscription->getKey(),
            'customer_count' => $this->customerCount,
            'included_customer_limit' => $this->includedLimit,
            'threshold_percent' => $this->thresholdPercent,
            'title' => [
                'en' => 'Customer usage warning',
                'ar' => 'تنبيه استهلاك العملاء',
            ],
            'message' => [
                'en' => "Customer usage reached {$this->thresholdPercent}% of the included limit.",
                'ar' => "وصل استخدام العملاء إلى {$this->thresholdPercent}% من الحد المضمن.",
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Customer usage warning')
            ->greeting('BookResa')
            ->line("Customer usage reached {$this->thresholdPercent}% of your included limit.")
            ->line("Current customers: {$this->customerCount}")
            ->line("Included customers: {$this->includedLimit}");
    }
}
