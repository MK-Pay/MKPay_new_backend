<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Tenant\App;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Transaction;
use App\Models\Tenant\TransactionStatus;
use App\Models\Tenant\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Transaction>
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(8, 10, 10000);
        $fee = $amount * 0.029;
        $netAmount = $amount - $fee;

        return [
            'account_id' => Account::factory(),
            'app_id' => App::factory(),
            'wallet_id' => Wallet::factory(),
            'currency_id' => Currency::factory(),
            'transaction_status_id' => TransactionStatus::factory(),
            'type' => fake()->randomElement(['credit', 'debit', 'transfer']),
            'amount' => (string) $amount,
            'fee' => (string) $fee,
            'net_amount' => (string) $netAmount,
            'payment_method' => fake()->randomElement(['credit_card', 'bank_transfer', 'pix', 'boleto']),
            'external_id' => fake()->uuid(),
            'description' => fake()->sentence(),
            'metadata' => [
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
            ],
            'related_transaction_id' => null,
            'completed_at' => now(),
        ];
    }

    /**
     * Indicate that the transaction is not completed.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the transaction is a credit.
     */
    public function credit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'credit',
        ]);
    }

    /**
     * Indicate that the transaction is a debit.
     */
    public function debit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'debit',
        ]);
    }

    /**
     * Indicate that the transaction is a transfer.
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'transfer',
        ]);
    }

    /**
     * Indicate that the transaction has no app.
     */
    public function noApp(): static
    {
        return $this->state(fn (array $attributes) => [
            'app_id' => null,
        ]);
    }

    /**
     * Indicate that the transaction has no metadata.
     */
    public function noMetadata(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => null,
        ]);
    }

    /**
     * Indicate that the transaction is related to another transaction.
     */
    public function withRelatedTransaction(): static
    {
        return $this->state(fn (array $attributes) => [
            'related_transaction_id' => Transaction::factory(),
        ]);
    }
}
