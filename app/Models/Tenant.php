<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDomains;
    use HasDatabase;

    /**
     * Get the domains for the tenant.
     */
    public function domains(): HasMany
    {
        return $this->hasMany(config('tenancy.domain_model'), 'tenant_id');
    }
}
