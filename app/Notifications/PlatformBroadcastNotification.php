<?php

namespace App\Notifications;

use App\Domain\Platform\Models\PlatformBroadcast;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class PlatformBroadcastNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly PlatformBroadcast $broadcast,
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'platform_broadcast',
            'broadcast_id' => $this->broadcast->getKey(),
            'tenant_id' => $this->broadcast->tenant_id,
            'title' => [
                'en' => $this->broadcast->title_en,
                'ar' => $this->broadcast->title_ar,
            ],
            'message' => [
                'en' => $this->broadcast->message_en,
                'ar' => $this->broadcast->message_ar,
            ],
        ];
    }
}
