<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallet_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('restrict');
            $table->decimal('balance', 20, 8)->default(0)->comment('Current balance in this currency');
            $table->decimal('held_balance', 20, 8)->default(0)->comment('Balance held/frozen for pending transactions');
            $table->decimal('available_balance', 20, 8)->default(0)->comment('Balance available for use (balance - held_balance)');
            $table->timestamps();

            $table->index('wallet_id');
            $table->index('currency_id');
            $table->unique(['wallet_id', 'currency_id'], 'unique_wallet_currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_balances');
    }
};
