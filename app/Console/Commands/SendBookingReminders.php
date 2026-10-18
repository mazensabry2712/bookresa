<?php

namespace App\Console\Commands;

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Customer\Models\Customer;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Notifications\BookingNotification;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

final class SendBookingReminders extends Command
{
    protected $signature = 'bookresa:send-booking-reminders {--hours=24 : Hours before the booking to notify} {--window=30 : Matching window in minutes}';

    protected $description = 'Queue reminder notifications for upcoming confirmed bookings.';

    public function handle(CurrentTenant $currentTenant): int
    {
        $hours = max((int) $this->option('hours'), 1);
        $window = max((int) $this->option('window'), 1);
        $now = CarbonImmutable::now('UTC');

        $from = $now->addHours($hours)->subMinutes($window);
        $to = $now->addHours($hours)->addMinutes($window);
        $sent = 0;
        $failed = 0;

        Booking::withoutGlobalScopes()
            ->where('status', BookingStatus::Confirmed->value)
            ->whereBetween('starts_at', [$from, $to])
            ->whereNull('reminder_sent_at')
            ->orderBy('id')
            ->chunkById(100, function (Collection $bookings) use ($currentTenant, &$sent, &$failed): void {
                /** @var Collection<int, Booking> $bookings */
                foreach ($bookings as $booking) {
                    $tenant = Tenant::query()->find((int) $booking->tenant_id);

                    if ($tenant === null) {
                        continue;
                    }

                    $currentTenant->set($tenant);

                    $claimedAt = now();

                    $claimed = Booking::withoutGlobalScopes()
                        ->whereKey($booking->getKey())
                        ->where('tenant_id', $tenant->getKey())
                        ->whereNull('reminder_sent_at')
                        ->update(['reminder_sent_at' => $claimedAt]);

                    if ($claimed !== 1) {
                        continue;
                    }

                    $fresh = Booking::query()
                        ->with(['customer', 'service'])
                        ->find($booking->getKey());
                    $customer = $fresh?->customer;

                    if (! $fresh instanceof Booking || ! $customer instanceof Customer) {
                        $this->releaseClaim($booking, $tenant, $claimedAt);

                        continue;
                    }

                    try {
                        $customer->notify(new BookingNotification($fresh, 'reminder'));
                        $sent++;
                    } catch (Throwable $exception) {
                        $this->releaseClaim($booking, $tenant, $claimedAt);
                        report($exception);
                        $failed++;
                    }
                }
            });

        $currentTenant->clear();

        $this->info("Queued {$sent} booking reminder(s); {$failed} failed reminder(s) released for retry.");

        return self::SUCCESS;
    }

    private function releaseClaim(Booking $booking, Tenant $tenant, mixed $claimedAt): void
    {
        Booking::withoutGlobalScopes()
            ->whereKey($booking->getKey())
            ->where('tenant_id', $tenant->getKey())
            ->where('reminder_sent_at', $claimedAt)
            ->update(['reminder_sent_at' => null]);
    }
}
