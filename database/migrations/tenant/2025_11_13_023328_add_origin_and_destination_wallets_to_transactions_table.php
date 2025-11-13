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
        Schema::table('transactions', function (Blueprint $table) {
            // Make wallet_id nullable since we'll use origin/destination instead
            $table->foreignId('wallet_id')->nullable()->change();

            // Add origin and destination wallet fields
            $table->foreignId('origin_wallet_id')->after('wallet_id')->nullable()->constrained('wallets')->onDelete('restrict')->comment('Source wallet for debits/transfers');
            $table->foreignId('destination_wallet_id')->after('origin_wallet_id')->nullable()->constrained('wallets')->onDelete('restrict')->comment('Target wallet for credits/transfers');

            $table->index('origin_wallet_id');
            $table->index('destination_wallet_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['origin_wallet_id']);
            $table->dropForeign(['destination_wallet_id']);
            $table->dropIndex(['origin_wallet_id']);
            $table->dropIndex(['destination_wallet_id']);
            $table->dropColumn(['origin_wallet_id', 'destination_wallet_id']);

            // Revert wallet_id to not nullable
            $table->foreignId('wallet_id')->nullable(false)->change();
        });
    }
};
