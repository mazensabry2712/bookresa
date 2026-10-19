<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Payment\Models\BookingCommission;
use App\Domain\Payment\Models\WorkspacePayout;
use App\Domain\Tenant\Models\Tenant;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class PlatformCommissionController
{
    public function index(): View
    {
        $this->authorizeFinance();

        $earned = BookingCommission::withoutGlobalScopes()
            ->selectRaw('tenant_id, currency, SUM(commission_amount_minor) as commission_minor, SUM(workspace_net_minor) as workspace_net_minor')
            ->groupBy('tenant_id', 'currency')
            ->orderBy('tenant_id')
            ->get();

        $reservedPayouts = WorkspacePayout::query()
            ->whereIn('status', ['pending', 'paid'])
            ->selectRaw('tenant_id, currency, SUM(amount_minor) as reserved_minor')
            ->groupBy('tenant_id', 'currency')
            ->get();

        $balances = $earned->map(function (object $row) use ($reservedPayouts): array {
            $tenant = Tenant::withoutGlobalScopes()
                ->with(['profile' => fn ($query) => $query->withoutGlobalScopes()])
                ->find($row->tenant_id);
            $reserved = (int) ($reservedPayouts->first(
                fn (object $payout): bool => (int) $payout->tenant_id === (int) $row->tenant_id
                    && $payout->currency === $row->currency,
            )?->reserved_minor ?? 0);
            $workspaceNet = (int) $row->workspace_net_minor;

            return [
                'tenant_id' => (int) $row->tenant_id,
                'tenant_name' => data_get($tenant?->profile?->name, app()->getLocale())
                    ?? data_get($tenant?->profile?->name, 'en')
                    ?? $tenant?->slug
                    ?? (string) $row->tenant_id,
                'slug' => (string) ($tenant?->slug ?? ''),
                'currency' => (string) $row->currency,
                'commission_minor' => (int) $row->commission_minor,
                'workspace_net_minor' => $workspaceNet,
                'reserved_minor' => $reserved,
                'available_minor' => $workspaceNet - $reserved,
            ];
        })->values();

        $relationLoader = [
            'tenant' => fn ($query) => $query->withoutGlobalScopes()
                ->with(['profile' => fn ($profileQuery) => $profileQuery->withoutGlobalScopes()]),
        ];

        return view('admin.commissions.index', [
            'balances' => $balances,
            'totalCommissionMinor' => (int) BookingCommission::withoutGlobalScopes()->sum('commission_amount_minor'),
            'totalPaidOutMinor' => (int) WorkspacePayout::query()->where('status', 'paid')->sum('amount_minor'),
            'commissions' => BookingCommission::withoutGlobalScopes()
                ->with([...$relationLoader, 'payment' => fn ($query) => $query->withoutGlobalScopes()])
                ->latest('id')
                ->paginate(30, ['*'], 'commissions_page'),
            'payouts' => WorkspacePayout::query()
                ->with([...$relationLoader, 'createdBy'])
                ->latest('id')
                ->paginate(20, ['*'], 'payouts_page'),
        ]);
    }

    public function storePayout(Request $request, AuditLogger $audit): RedirectResponse
    {
        $this->authorizeFinance();

        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'currency' => ['required', 'string', 'size:3'],
            'amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'payout_method' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $currency = strtoupper(trim($validated['currency']));
        $amountMinor = $this->moneyToMinor((string) $validated['amount']);

        if ($amountMinor <= 0) {
            throw ValidationException::withMessages(['amount' => __('Enter a payout amount greater than zero.')]);
        }

        $tenantId = (int) $validated['tenant_id'];
        $payout = DB::transaction(function () use ($tenantId, $currency, $amountMinor, $validated): WorkspacePayout {
            Tenant::withoutGlobalScopes()->whereKey($tenantId)->lockForUpdate()->firstOrFail();

            $earnedMinor = (int) BookingCommission::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('currency', $currency)
                ->sum('workspace_net_minor');
            $reservedMinor = (int) WorkspacePayout::query()
                ->where('tenant_id', $tenantId)
                ->where('currency', $currency)
                ->whereIn('status', ['pending', 'paid'])
                ->sum('amount_minor');
            $availableMinor = $earnedMinor - $reservedMinor;

            if ($amountMinor > $availableMinor) {
                throw ValidationException::withMessages([
                    'amount' => __('Payout amount exceeds the available workspace balance.'),
                ]);
            }

            return WorkspacePayout::query()->create([
                'tenant_id' => $tenantId,
                'reference' => 'PAYOUT-'.Str::upper(Str::random(12)),
                'currency' => $currency,
                'amount_minor' => $amountMinor,
                'status' => 'pending',
                'payout_method' => trim((string) ($validated['payout_method'] ?? '')) ?: null,
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
                'created_by' => (int) auth()->id(),
                'metadata' => ['source' => 'platform_commission_ledger'],
            ]);
        });

        $audit->log('platform.workspace_payout_reserved', $payout, [
            'tenant_id' => $tenantId,
            'payout_id' => (int) $payout->getKey(),
            'reference' => $payout->reference,
            'amount_minor' => $amountMinor,
            'currency' => $currency,
        ]);

        return back()->with('status', __('Payout reserved successfully.'));
    }

    public function markPayoutPaid(Request $request, int $payout, AuditLogger $audit): RedirectResponse
    {
        $this->authorizeFinance();
        $validated = $request->validate([
            'payout_reference' => ['required', 'string', 'max:128'],
        ]);

        $workspacePayout = DB::transaction(function () use ($payout, $validated): WorkspacePayout {
            $workspacePayout = WorkspacePayout::query()->lockForUpdate()->findOrFail($payout);

            if ($workspacePayout->status !== 'pending') {
                throw ValidationException::withMessages([
                    'payout' => __('Only pending payouts can be marked as paid.'),
                ]);
            }

            $workspacePayout->forceFill([
                'status' => 'paid',
                'payout_reference' => trim($validated['payout_reference']),
                'paid_at' => now(),
            ])->save();

            return $workspacePayout;
        });

        $audit->log('platform.workspace_payout_marked_paid', $workspacePayout, [
            'tenant_id' => (int) $workspacePayout->tenant_id,
            'payout_id' => (int) $workspacePayout->getKey(),
            'reference' => $workspacePayout->reference,
            'payout_reference' => $workspacePayout->payout_reference,
            'amount_minor' => (int) $workspacePayout->amount_minor,
            'currency' => $workspacePayout->currency,
        ]);

        return back()->with('status', __('Payout marked as paid.'));
    }

    public function cancelPayout(int $payout, AuditLogger $audit): RedirectResponse
    {
        $this->authorizeFinance();

        $workspacePayout = DB::transaction(function () use ($payout): WorkspacePayout {
            $workspacePayout = WorkspacePayout::query()->lockForUpdate()->findOrFail($payout);

            if ($workspacePayout->status !== 'pending') {
                throw ValidationException::withMessages([
                    'payout' => __('Only pending payouts can be cancelled.'),
                ]);
            }

            $workspacePayout->forceFill(['status' => 'cancelled'])->save();

            return $workspacePayout;
        });

        $audit->log('platform.workspace_payout_reservation_cancelled', $workspacePayout, [
            'tenant_id' => (int) $workspacePayout->tenant_id,
            'payout_id' => (int) $workspacePayout->getKey(),
            'reference' => $workspacePayout->reference,
        ]);

        return back()->with('status', __('Payout reservation cancelled.'));
    }

    private function authorizeFinance(): void
    {
        abort_unless(auth()->user()?->platformAdmin?->hasPlatformPermission('finance.manage') === true, 403);
    }

    private function moneyToMinor(string $value): int
    {
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw ValidationException::withMessages(['amount' => __('Enter an amount with no more than two decimal places.')]);
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
