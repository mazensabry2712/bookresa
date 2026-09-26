<?php

namespace App\Domain\Staff\Actions;

use App\Domain\Identity\Services\TenantRoleProvisioner;
use App\Domain\Staff\Enums\StaffStatus;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Models\TenantMembership;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class UpdateStaffMember
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly TenantRoleProvisioner $roleProvisioner,
    ) {
    }

    public function handle(StaffProfile $staff, array $data): StaffProfile
    {
        $tenant = $this->currentTenant->get();
        $tenantId = $this->currentTenant->idOrFail();

        if ($tenant === null || (int) $staff->tenant_id !== $tenantId) {
            throw new RuntimeException('Staff member must belong to the current tenant.');
        }

        $role = (string) $data['role'];
        $roles = config('bookresa.rbac.roles', []);

        if (! array_key_exists($role, $roles) || $role === 'owner') {
            throw new RuntimeException('Invalid staff role.');
        }

        $status = StaffStatus::from((string) $data['status']);

        return DB::transaction(function () use ($staff, $data, $tenant, $tenantId, $role, $status): StaffProfile {
            $staff->update([
                'display_name' => $data['display_name'],
                'phone' => $data['phone'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'status' => $status,
            ]);

            $membership = TenantMembership::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $staff->user_id)
                ->firstOrFail();

            $membership->update([
                'status' => $status === StaffStatus::Active
                    ? MembershipStatus::Active
                    : MembershipStatus::Inactive,
            ]);

            $roleModel = $this->roleProvisioner->provisionRole($tenant, $role);

            $previousTeamId = getPermissionsTeamId();
            setPermissionsTeamId($tenantId);

            try {
                $staff->user->syncRoles([$roleModel]);
            } finally {
                setPermissionsTeamId($previousTeamId);
            }

            return $staff->fresh(['user', 'services']);
        });
    }
}
