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
        Schema::create('currency_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_currency_id')->constrained('currencies')->onDelete('restrict');
            $table->foreignId('to_currency_id')->constrained('currencies')->onDelete('restrict');
            $table->decimal('rate', 20, 10)->comment('Exchange rate from source to target currency');
            $table->decimal('fee_percentage', 5, 2)->default(0)->comment('Fee percentage for currency exchange');
            $table->decimal('fee_fixed', 20, 8)->default(0)->comment('Fixed fee for currency exchange');
            $table->boolean('is_active')->default(true);
            $table->timestamp('valid_from');
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();

            $table->index('from_currency_id');
            $table->index('to_currency_id');
            $table->index('is_active');
            $table->index('valid_from');
            $table->index('valid_until');
            $table->index(['from_currency_id', 'to_currency_id', 'is_active'], 'idx_currency_pair_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_exchange_rates');
    }
};
