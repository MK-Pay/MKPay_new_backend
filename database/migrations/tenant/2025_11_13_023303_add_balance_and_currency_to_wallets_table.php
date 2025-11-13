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
        Schema::table('wallets', function (Blueprint $table) {
            $table->foreignId('currency_id')->after('app_id')->constrained('currencies')->onDelete('restrict');
            $table->decimal('available_balance', 20, 8)->after('currency_id')->default(0)->comment('Available balance for transactions');
            $table->decimal('held_balance', 20, 8)->after('available_balance')->default(0)->comment('Balance on hold (pending transactions)');

            $table->index('currency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropIndex(['currency_id']);
            $table->dropColumn(['currency_id', 'available_balance', 'held_balance']);
        });
    }
};
