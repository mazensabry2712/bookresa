<?php

namespace App\Domain\Module\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'is_core',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'is_core' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Domain\Tenant\Models\Tenant::class,
            'tenant_modules',
        )->withPivot(['enabled', 'settings'])->withTimestamps();
    }
}
