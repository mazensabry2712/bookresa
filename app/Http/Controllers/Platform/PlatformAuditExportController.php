<?php

namespace App\Http\Controllers\Platform;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Spatie\Activitylog\Models\Activity;

final class PlatformAuditExportController
{
    public function __invoke(Request $request): StreamedResponse
    {
        $tenantId = $request->integer('tenant_id');
        $adminId = $request->integer('admin_id');
        $search = trim((string) $request->input('search'));
        $from = $request->input('from');
        $to = $request->input('to');

        $activities = Activity::query()
            ->where('log_name', 'security')
            ->when($tenantId > 0, fn ($query) => $query->where('properties->tenant_id', $tenantId))
            ->when($adminId > 0, fn ($query) => $query->where('causer_id', $adminId))
            ->when($search !== '', fn ($query) => $query->where('description', 'like', '%'.$search.'%'))
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->with('causer')
            ->latest('id')
            ->limit(5000)
            ->get();

        return response()->streamDownload(function () use ($activities): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, [
                'id',
                'occurred_at',
                'action',
                'actor',
                'target',
                'tenant_id',
                'ip_address',
                'route',
                'user_agent',
            ]);

            foreach ($activities as $activity) {
                $properties = $activity->properties instanceof \Illuminate\Support\Collection
                    ? $activity->properties->toArray()
                    : (array) $activity->properties;
                $cells = [
                    $activity->getKey(),
                    $activity->created_at?->toIso8601String(),
                    $activity->description,
                    $activity->causer instanceof User ? $activity->causer->email : '',
                    $activity->subject_type ? $activity->subject_type.'#'.$activity->subject_id : '',
                    data_get($properties, 'tenant_id'),
                    data_get($properties, 'ip_address'),
                    data_get($properties, 'route'),
                    data_get($properties, 'user_agent'),
                ];

                fputcsv($handle, array_map(function ($value): string {
                    $value = (string) ($value ?? '');
                    return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
                }, $cells));
            }

            fclose($handle);
        }, 'bookresa-audit-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
