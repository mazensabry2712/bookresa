<?php

namespace App\Domain\Staff\Actions;

use App\Domain\Identity\Services\TenantRoleProvisioner;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Models\TenantMembership;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class AddStaffMember
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly TenantRoleProvisioner $roleProvisioner,
        private readonly AuditLogger $audit,
    ) {
    }

    public function handle(User $user, string $role = 'staff', array $data = []): StaffProfile
    {
        $tenantId = $this->currentTenant->idOrFail();

        if (! array_key_exists($role, config('bookresa.rbac.roles', [])) || $role === 'owner') {
            throw new RuntimeException('Invalid staff role.');
        }

        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($tenantId);

        try {
            if ($user->hasRole('owner')) {
                throw new RuntimeException('The workspace owner cannot be added as staff.');
            }
        } finally {
            setPermissionsTeamId($previousTeamId);
        }

        return DB::transaction(function () use ($user, $role, $data, $tenantId): StaffProfile {
            $membership = TenantMembership::query()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'user_id' => $user->getKey(),
                ],
                [
                    'status' => MembershipStatus::Active,
                    'is_primary' => false,
                ],
            );

            if ($membership->status !== MembershipStatus::Active) {
                $membership->update(['status' => MembershipStatus::Active]);
            }

            $staff = StaffProfile::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'user_id' => $user->getKey(),
                ],
                [
                    'tenant_id' => $tenantId,
                    'display_name' => $data['display_name'] ?? $user->name,
                    'phone' => $data['phone'] ?? null,
                    'job_title' => $data['job_title'] ?? null,
                    'avatar_path' => $data['avatar_path'] ?? null,
                    'status' => 'active',
                    'settings' => $data['settings'] ?? null,
                ],
            );

            $roleModel = $this->roleProvisioner->provisionRole(
                $this->currentTenant->get(),
                $role,
            );

            $previousTeamId = getPermissionsTeamId();
            setPermissionsTeamId($tenantId);

            try {
                $user->syncRoles([$roleModel]);
            } finally {
                setPermissionsTeamId($previousTeamId);
            }

            $fresh = $staff->fresh(['user', 'services']);

            $this->audit->log(
                'staff.role_assigned',
                $fresh,
                [
                    'tenant_id' => (int) $fresh->tenant_id,
                    'staff_id' => (int) $fresh->getKey(),
                    'user_id' => (int) $fresh->user_id,
                    'role' => $role,
                ],
            );

            return $fresh;
        });
    }
}
