
<?php

namespace App\Console\Commands;

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Notifications\BookingNotification;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

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

        Booking::withoutGlobalScopes()
            ->where('status', BookingStatus::Confirmed->value)
            ->whereBetween('starts_at', [$from, $to])
            ->whereNull('reminder_sent_at')
            ->orderBy('id')
            ->chunkById(100, function (Collection $bookings) use ($currentTenant, &$sent): void {
                /** @var Collection<int, Booking> $bookings */
                foreach ($bookings as $booking) {
                    $tenant = Tenant::query()->find((int) $booking->tenant_id);

                    if ($tenant === null) {
                        continue;
                    }

                    $currentTenant->set($tenant);

                    $claimed = Booking::withoutGlobalScopes()
                        ->whereKey($booking->getKey())
                        ->where('tenant_id', $tenant->getKey())
                        ->whereNull('reminder_sent_at')
                        ->update(['reminder_sent_at' => now()]);

                    if ($claimed !== 1) {
                        continue;
                    }

                    $fresh = Booking::query()
                        ->with(['customer', 'service'])
                        ->find($booking->getKey());

                    $fresh?->customer?->notify(new BookingNotification($fresh, 'reminder'));
                    $sent++;
                }
            });

        $currentTenant->clear();

        $this->info("Queued {$sent} booking reminder(s).");

        return self::SUCCESS;
    }
}
