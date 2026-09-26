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
        [$titleEn, $titleAr, $messageEn, $messageAr] = match (true) {
            $this->includedLimit > 0 && $this->customerCount > $this->includedLimit => [
                'Customer usage over limit',
                'تم تجاوز حد العملاء',
                "You have {$this->customerCount} customers. {$this->customerCount - $this->includedLimit} additional customers are currently billed according to your plan.",
                "لديك {$this->customerCount} عميلًا. يتم احتساب {$this->customerCount - $this->includedLimit} عميل إضافي حاليًا وفقًا لخطتك.",
            ],
            $this->includedLimit > 0 && $this->customerCount >= $this->includedLimit => [
                'Customer limit reached',
                'تم الوصول إلى حد العملاء',
                "You have reached {$this->includedLimit} included customers.",
                "لقد وصلت إلى حد {$this->includedLimit} عميلًا المضمن في خطتك.",
            ],
            default => [
                'Customer usage warning',
                'تنبيه استهلاك العملاء',
                "Customer usage reached {$this->thresholdPercent}% of the included limit.",
                "وصل استخدام العملاء إلى {$this->thresholdPercent}% من الحد المضمن.",
            ],
        ];

        return [
            'type' => 'usage_warning',
            'tenant_id' => $this->subscription->tenant_id,
            'subscription_id' => $this->subscription->getKey(),
            'customer_count' => $this->customerCount,
            'included_customer_limit' => $this->includedLimit,
            'threshold_percent' => $this->thresholdPercent,
            'title' => [
                'en' => $titleEn,
                'ar' => $titleAr,
            ],
            'message' => [
                'en' => $messageEn,
                'ar' => $messageAr,
            ],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$titleEn, , $messageEn, ] = $this->content();

        return (new MailMessage)
            ->subject($titleEn)
            ->greeting('Velto')
            ->line($messageEn)
            ->line("Current customers: {$this->customerCount}")
            ->line("Included customers: {$this->includedLimit}");
    }

    /** @return array{0:string,1:string,2:string,3:string} */
    private function content(): array
    {
        return match (true) {
            $this->includedLimit > 0 && $this->customerCount > $this->includedLimit => [
                'Customer usage over limit',
                'تم تجاوز حد العملاء',
                "You have {$this->customerCount} customers. {$this->customerCount - $this->includedLimit} additional customers are currently billed according to your plan.",
                "لديك {$this->customerCount} عميلًا. يتم احتساب ".($this->customerCount - $this->includedLimit)." عميل إضافي حاليًا وفقًا لخطتك.",
            ],
            $this->includedLimit > 0 && $this->customerCount >= $this->includedLimit => [
                'Customer limit reached',
                'تم الوصول إلى حد العملاء',
                "You have reached {$this->includedLimit} included customers.",
                "لقد وصلت إلى حد {$this->includedLimit} عميلًا المضمن في خطتك.",
            ],
            default => [
                'Customer usage warning',
                'تنبيه استهلاك العملاء',
                "Customer usage reached {$this->thresholdPercent}% of the included limit.",
                "وصل استخدام العملاء إلى {$this->thresholdPercent}% من الحد المضمن.",
            ],
        };
    }
}
