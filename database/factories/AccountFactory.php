<?php

namespace Database\Factories;

use App\Models\Tenant\Account;
use App\Models\Tenant\AccountCategory;
use App\Models\Tenant\AccountStatus;
use App\Models\Tenant\AccountType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Account>
     */
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_type_id' => AccountType::factory(),
            'account_category_id' => AccountCategory::factory(),
            'account_status_id' => AccountStatus::factory(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'name' => fake()->name(),
            'cpf' => fake()->numerify('###########'),
            'cnpj' => null,
            'phone' => fake()->phoneNumber(),
            'usage_types' => ['payment', 'transfer'],
            'hourly_transaction_limit' => '10000.00',
            'daily_transaction_limit' => '50000.00',
            'verified_at' => now(),
        ];
    }

    /**
     * Indicate that the account is unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
            'verified_at' => null,
        ]);
    }

    /**
     * Indicate that the account is a company account.
     */
    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'cpf' => null,
            'cnpj' => fake()->numerify('##############'),
            'name' => fake()->company(),
        ]);
    }

    /**
     * Indicate that the account has no category.
     */
    public function noCategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_category_id' => null,
        ]);
    }

    /**
     * Indicate that the account has no transaction limits.
     */
    public function noLimits(): static
    {
        return $this->state(fn (array $attributes) => [
            'hourly_transaction_limit' => null,
            'daily_transaction_limit' => null,
        ]);
    }
}
