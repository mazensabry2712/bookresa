<?php

namespace App\Domain\Identity\Services;

use App\Domain\Tenant\Models\Tenant;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Spatie\Permission\Models\Role;

final class TenantRoleProvisioner
{
    public function provisionOwner(Tenant $tenant, User $owner): Role
    {
        $role = $this->provisionRole($tenant, 'owner');

        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($tenant->getKey());

        try {
            $owner->assignRole($role);
        } finally {
            setPermissionsTeamId($previousTeamId);
        }

        return $role;
    }

    public function provisionRole(Tenant $tenant, string $roleKey): Role
    {
        $permissions = config("bookresa.rbac.roles.{$roleKey}", []);

        if (! is_array($permissions)) {
            throw new \InvalidArgumentException("Unknown BookResa role [{$roleKey}].");
        }

        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($tenant->getKey());

        try {
            $permissionModels = collect($permissions)
                ->map(fn (string $permission): Permission => Permission::findOrCreate($permission, 'web'))
                ->all();

            $role = Role::firstOrCreate([
                'name' => $roleKey,
                'guard_name' => 'web',
                'tenant_id' => $tenant->getKey(),
            ]);

            $role->syncPermissions($permissionModels);

            return $role;
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }
}
