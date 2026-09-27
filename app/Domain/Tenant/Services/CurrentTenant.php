<?php

namespace App\Domain\Tenant\Services;

use App\Domain\Tenant\Models\Tenant;
use Closure;

final class CurrentTenant
{
    public function set(Tenant $tenant): void
    {
        $tenant->makeCurrent();
    }

    public function clear(): void
    {
        Tenant::forgetCurrent();
    }

    public function get(): ?Tenant
    {
        return Tenant::current();
    }

    public function id(): ?int
    {
        return $this->get()?->getKey();
    }

    public function idOrFail(): int
    {
        $id = $this->id();

        if ($id === null) {
            throw new \LogicException('A current tenant is required for this operation.');
        }

        return $id;
    }

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function run(Tenant $tenant, Closure $callback): mixed
    {
        $previousTenant = $this->get();

        if ($previousTenant?->getKey() !== $tenant->getKey()) {
            $tenant->makeCurrent();
        }

        try {
            return $callback();
        } finally {
            if ($previousTenant !== null && $previousTenant->getKey() !== $tenant->getKey()) {
                $previousTenant->makeCurrent();
            } elseif ($previousTenant === null) {
                Tenant::forgetCurrent();
            }
        }
    }
}
