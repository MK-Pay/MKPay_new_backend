<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Stancl\Tenancy\Database\Models\Tenant;

abstract class TenantTestCase extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Create and initialize a test tenant
        $this->tenant = Tenant::create();
        tenancy()->initialize($this->tenant);

        // Run tenant migrations
        $this->artisan('tenants:migrate', [
            '--tenants' => [$this->tenant->id],
        ])->execute();

        // Seed tenant configuration data
        $this->seed(\Database\Seeders\TenantConfigSeeder::class);
    }

    protected function tearDown(): void
    {
        // Clean up tenant
        tenancy()->end();

        parent::tearDown();
    }
}
