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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('account_id')->constrained('accounts')->onDelete('restrict');
            $table->foreignId('app_id')->nullable()->constrained('apps')->onDelete('restrict');
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('restrict');
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('restrict');
            $table->foreignId('transaction_status_id')->constrained('transaction_status')->onDelete('restrict');
            $table->string('type', 50)->comment('payment, withdrawal, transfer, exchange, refund, fee, etc.');
            $table->decimal('amount', 20, 8);
            $table->decimal('fee', 20, 8)->default(0);
            $table->decimal('net_amount', 20, 8)->comment('Amount after fees');
            $table->string('payment_method', 50)->nullable()->comment('PIX, credit_card, bank_transfer, etc.');
            $table->string('external_id')->nullable()->comment('Payment acquirer transaction ID');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable()->comment('Additional transaction data');
            $table->foreignId('related_transaction_id')->nullable()->constrained('transactions')->onDelete('set null')->comment('For refunds, transfers, etc.');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('uuid');
            $table->index('account_id');
            $table->index('app_id');
            $table->index('wallet_id');
            $table->index('currency_id');
            $table->index('transaction_status_id');
            $table->index('type');
            $table->index('external_id');
            $table->index('completed_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
