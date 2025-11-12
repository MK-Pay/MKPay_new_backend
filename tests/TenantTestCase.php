<?php

namespace Tests;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

abstract class TenantTestCase extends TestCase
{
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Refresh central database
        $this->artisan('migrate:fresh', ['--database' => config('tenancy.database.central_connection')]);

        // Create a test tenant in the central database
        $this->tenant = Tenant::create();

        // Create tenant schema in PostgreSQL
        $tenantSchema = 'tenant' . $this->tenant->id;

        try {
            DB::connection(config('tenancy.database.central_connection', 'central'))
                ->statement("CREATE SCHEMA IF NOT EXISTS \"{$tenantSchema}\"");
        } catch (\Throwable $e) {
            // Ignore errors
        }

        // Initialize tenancy
        tenancy()->initialize($this->tenant);

        // Run tenant migrations
        $this->artisan('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);

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
