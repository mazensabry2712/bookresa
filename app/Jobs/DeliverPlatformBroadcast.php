<?php

namespace App\Jobs;

use App\Domain\Platform\Models\PlatformBroadcast;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Models\User;
use App\Notifications\PlatformBroadcastNotification;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Multitenancy\Jobs\NotTenantAware;

final class DeliverPlatformBroadcast implements ShouldQueue, NotTenantAware
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $broadcastId,
    ) {
        $this->afterCommit();
    }

    public function handle(): void
    {
        $broadcast = PlatformBroadcast::query()->findOrFail($this->broadcastId);

        $query = User::query()
            ->whereHas('tenantMemberships', function ($membership) use ($broadcast): void {
                $membership
                    ->where('status', MembershipStatus::Active->value)
                    ->when($broadcast->tenant_id !== null, fn ($q) => $q->where('tenant_id', $broadcast->tenant_id));
            })
            ->select('users.*')
            ->distinct()
            ->orderBy('users.id');

        $count = 0;

        $query->chunkById(200, function ($users) use ($broadcast, &$count): void {
            foreach ($users as $user) {
                $user->notify(new PlatformBroadcastNotification($broadcast));
                $count++;
            }
        });

        $broadcast->forceFill([
            'status' => 'sent',
            'recipients_count' => $count,
            'sent_at' => now(),
            'error' => null,
        ])->save();
    }

    public function failed(\Throwable $exception): void
    {
        PlatformBroadcast::query()
            ->whereKey($this->broadcastId)
            ->update([
                'status' => 'failed',
                'error' => mb_substr($exception->getMessage(), 0, 5000),
            ]);
    }
}
