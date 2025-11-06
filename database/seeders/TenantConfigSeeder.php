<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantConfigSeeder extends Seeder
{
    /**
     * Run the database seeds for tenant configuration tables.
     */
    public function run(): void
    {
        $this->seedAccountTypes();
        $this->seedAccountCategories();
        $this->seedAccountStatus();
        $this->seedCurrencies();
        $this->seedTransactionStatus();
    }

    private function seedAccountTypes(): void
    {
        $types = [
            [
                'code' => 'PF',
                'name' => 'Individual',
                'description' => 'Pessoa Física - Individual account for personal use',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'PJ',
                'name' => 'Business',
                'description' => 'Pessoa Jurídica - Business account for companies',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('account_types')->insert($types);
    }

    private function seedAccountCategories(): void
    {
        $categories = [
            ['name' => 'E-commerce', 'slug' => 'ecommerce', 'description' => 'Online stores and marketplaces', 'is_active' => true],
            ['name' => 'SaaS', 'slug' => 'saas', 'description' => 'Software as a Service businesses', 'is_active' => true],
            ['name' => 'Digital Services', 'slug' => 'digital-services', 'description' => 'Digital service providers', 'is_active' => true],
            ['name' => 'Education', 'slug' => 'education', 'description' => 'Educational institutions and online courses', 'is_active' => true],
            ['name' => 'Healthcare', 'slug' => 'healthcare', 'description' => 'Healthcare and medical services', 'is_active' => true],
            ['name' => 'Food & Beverage', 'slug' => 'food-beverage', 'description' => 'Restaurants, delivery, and food services', 'is_active' => true],
            ['name' => 'Retail', 'slug' => 'retail', 'description' => 'Physical and online retail stores', 'is_active' => true],
            ['name' => 'Technology', 'slug' => 'technology', 'description' => 'Technology companies and IT services', 'is_active' => true],
            ['name' => 'Financial Services', 'slug' => 'financial-services', 'description' => 'Financial and investment services', 'is_active' => true],
            ['name' => 'Entertainment', 'slug' => 'entertainment', 'description' => 'Entertainment and media companies', 'is_active' => true],
            ['name' => 'Non-Profit', 'slug' => 'non-profit', 'description' => 'Non-profit organizations and charities', 'is_active' => true],
            ['name' => 'Other', 'slug' => 'other', 'description' => 'Other business categories', 'is_active' => true],
        ];

        foreach ($categories as $category) {
            DB::table('account_categories')->insert([
                ...$category,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedAccountStatus(): void
    {
        $statuses = [
            [
                'code' => 'pending',
                'name' => 'Pending Verification',
                'description' => 'Account created but not verified. Limited functionality.',
                'allows_transactions' => true,
                'allows_withdrawals' => false,
                'allows_transfers' => false,
                'holds_funds' => true,
                'is_active' => true,
            ],
            [
                'code' => 'validated',
                'name' => 'Validated',
                'description' => 'Email validated but documents not fully verified. Some limitations apply.',
                'allows_transactions' => true,
                'allows_withdrawals' => true,
                'allows_transfers' => true,
                'holds_funds' => false,
                'is_active' => true,
            ],
            [
                'code' => 'verified',
                'name' => 'Fully Verified',
                'description' => 'Fully verified account with all features enabled.',
                'allows_transactions' => true,
                'allows_withdrawals' => true,
                'allows_transfers' => true,
                'holds_funds' => false,
                'is_active' => true,
            ],
            [
                'code' => 'suspended',
                'name' => 'Suspended',
                'description' => 'Account suspended. All operations blocked.',
                'allows_transactions' => false,
                'allows_withdrawals' => false,
                'allows_transfers' => false,
                'holds_funds' => true,
                'is_active' => true,
            ],
            [
                'code' => 'blocked',
                'name' => 'Blocked',
                'description' => 'Account blocked due to policy violation. No operations allowed.',
                'allows_transactions' => false,
                'allows_withdrawals' => false,
                'allows_transfers' => false,
                'holds_funds' => true,
                'is_active' => true,
            ],
            [
                'code' => 'closed',
                'name' => 'Closed',
                'description' => 'Account permanently closed.',
                'allows_transactions' => false,
                'allows_withdrawals' => false,
                'allows_transfers' => false,
                'holds_funds' => false,
                'is_active' => false,
            ],
        ];

        foreach ($statuses as $status) {
            DB::table('account_status')->insert([
                ...$status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedCurrencies(): void
    {
        $currencies = [
            [
                'code' => 'BRL',
                'name' => 'Brazilian Real',
                'symbol' => 'R$',
                'decimal_places' => 2,
                'is_active' => true,
                'is_national' => true, // Only BRL can be withdrawn
            ],
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'decimal_places' => 2,
                'is_active' => true,
                'is_national' => false,
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'decimal_places' => 2,
                'is_active' => true,
                'is_national' => false,
            ],
            [
                'code' => 'GBP',
                'name' => 'British Pound',
                'symbol' => '£',
                'decimal_places' => 2,
                'is_active' => true,
                'is_national' => false,
            ],
        ];

        foreach ($currencies as $currency) {
            DB::table('currencies')->insert([
                ...$currency,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedTransactionStatus(): void
    {
        $statuses = [
            [
                'code' => 'pending',
                'name' => 'Pending',
                'description' => 'Transaction created and awaiting processing',
                'is_final' => false,
                'is_active' => true,
            ],
            [
                'code' => 'processing',
                'name' => 'Processing',
                'description' => 'Transaction is being processed',
                'is_final' => false,
                'is_active' => true,
            ],
            [
                'code' => 'authorized',
                'name' => 'Authorized',
                'description' => 'Transaction authorized but not yet captured',
                'is_final' => false,
                'is_active' => true,
            ],
            [
                'code' => 'completed',
                'name' => 'Completed',
                'description' => 'Transaction successfully completed',
                'is_final' => true,
                'is_active' => true,
            ],
            [
                'code' => 'failed',
                'name' => 'Failed',
                'description' => 'Transaction failed to process',
                'is_final' => true,
                'is_active' => true,
            ],
            [
                'code' => 'cancelled',
                'name' => 'Cancelled',
                'description' => 'Transaction cancelled by user or system',
                'is_final' => true,
                'is_active' => true,
            ],
            [
                'code' => 'refunded',
                'name' => 'Refunded',
                'description' => 'Transaction has been refunded',
                'is_final' => true,
                'is_active' => true,
            ],
            [
                'code' => 'partially_refunded',
                'name' => 'Partially Refunded',
                'description' => 'Transaction has been partially refunded',
                'is_final' => false,
                'is_active' => true,
            ],
            [
                'code' => 'chargeback',
                'name' => 'Chargeback',
                'description' => 'Transaction disputed and charged back',
                'is_final' => true,
                'is_active' => true,
            ],
            [
                'code' => 'expired',
                'name' => 'Expired',
                'description' => 'Transaction expired without completion',
                'is_final' => true,
                'is_active' => true,
            ],
        ];

        foreach ($statuses as $status) {
            DB::table('transaction_status')->insert([
                ...$status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
