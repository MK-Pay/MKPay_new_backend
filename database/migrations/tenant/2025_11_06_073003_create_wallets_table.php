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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('account_id')->constrained('public.accounts')->onDelete('cascade');
            $table->foreignId('app_id')->nullable()->constrained('apps')->onDelete('cascade')->comment('NULL for main wallet, app_id for app-specific wallets');
            $table->boolean('is_main')->default(false)->comment('One main wallet per account');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('uuid');
            $table->index('account_id');
            $table->index('app_id');
            $table->index('is_main');
            $table->index('is_active');
            $table->unique(['account_id', 'app_id'], 'unique_account_app_wallet');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
