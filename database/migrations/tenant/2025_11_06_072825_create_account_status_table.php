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
        Schema::create('account_status', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('pending, validated, verified, suspended, etc.');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('allows_transactions')->default(false)->comment('If account can perform transactions');
            $table->boolean('allows_withdrawals')->default(false)->comment('If account can withdraw funds');
            $table->boolean('allows_transfers')->default(false)->comment('If account can transfer funds');
            $table->boolean('holds_funds')->default(true)->comment('If funds are held/frozen');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_status');
    }
};
