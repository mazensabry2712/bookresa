<?php

namespace App\Domain\Platform\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformAdmin extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'is_active',
        'role',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'permissions' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasPlatformPermission(string $permission): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $permissions = array_merge(
            (array) data_get(config("platform.roles.{$this->role}"), 'permissions', []),
            is_array($this->permissions) ? $this->permissions : [],
        );

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    public function hasTenantPermission(string $permission): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $permissions = data_get(config("platform.roles.{$this->role}"), 'tenant_permissions', []);

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    public function roleLabel(): string
    {
        return (string) data_get(
            config("platform.roles.{$this->role}"),
            'label',
            str($this->role)->replace('_', ' ')->title(),
        );
    }

    public function availablePlatformPermissions(): array
    {
        return array_values(config('platform.permissions', []));
    }
}
