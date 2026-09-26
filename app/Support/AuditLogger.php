<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

final class AuditLogger
{
    /**
     * @param array<string, mixed> $properties
     */
    public function log(
        string $description,
        ?Model $subject = null,
        array $properties = [],
    ): void {
        $activity = activity('security');

        if ($subject !== null) {
            $activity->performedOn($subject);
        }

        if (auth()->check()) {
            $activity->causedBy(auth()->user());
        }

        if ($properties !== []) {
            $activity->withProperties($properties);
        }

        $activity->log($description);
    }
}
