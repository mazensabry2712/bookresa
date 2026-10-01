<?php

namespace App\Support;

use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class AuditLogger
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    /**
     * @param array<string, mixed> $properties
     */
    public function log(
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?Model $causer = null,
    ): void {
        if (! array_key_exists('tenant_id', $properties)) {
            $tenant = $this->currentTenant->get();

            if ($tenant !== null) {
                $properties['tenant_id'] = (int) $tenant->getKey();
            }
        }

        if (! app()->runningInConsole()) {
            $properties = array_merge([
                'ip' => request()->ip(),
                'route' => request()->route()?->getName(),
                'user_agent' => Str::limit((string) request()->userAgent(), 500, ''),
            ], $properties);
        }

        $activity = activity('security');

        if ($subject !== null) {
            $activity->performedOn($subject);
        }

        $causer ??= auth()->user();

        if ($causer !== null) {
            $activity->causedBy($causer);
        }

        if ($properties !== []) {
            $activity->withProperties($properties);
        }

        $activity->log($description);
    }
}
