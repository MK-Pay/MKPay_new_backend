<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

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

        // Ensure tenant schema exists and set search_path for PostgreSQL tenancy
        $tenantSchema = 'tenant' . $this->tenant->id;

        try {
            DB::statement("CREATE SCHEMA IF NOT EXISTS \"{$tenantSchema}\"");
            DB::statement("SET search_path TO \"{$tenantSchema}\", public");
        } catch (\Throwable $e) {
            // Ignore if not applicable in test environment
        }

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
