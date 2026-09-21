<?php

namespace App\Domain\Identity\Services;

use App\Domain\Tenant\Models\Tenant;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class TenantRoleProvisioner
{
    public function provisionOwner(Tenant $tenant): Role
    {
        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($tenant->getKey());

        try {
            $permissions = collect(config('bookresa.rbac.roles.owner', []))
                ->map(fn (string $permission): Permission => Permission::findOrCreate($permission, 'web'))
                ->all();

            $role = Role::firstOrCreate([
                'name' => 'owner',
                'guard_name' => 'web',
                'tenant_id' => $tenant->getKey(),
            ]);

            $role->syncPermissions($permissions);

            return $role;
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }
}
