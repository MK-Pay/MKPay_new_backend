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
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->foreignId('app_id')->nullable()->constrained('apps')->onDelete('cascade');
            $table->string('url');
            $table->string('secret')->nullable()->comment('Secret for webhook signature verification');
            $table->json('events')->comment('Array of events to listen to: transaction.*, payment.*, etc.');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('retry_count')->default(3)->comment('Number of retry attempts on failure');
            $table->unsignedInteger('timeout')->default(30)->comment('Timeout in seconds');
            $table->timestamps();
            $table->softDeletes();

            $table->index('account_id');
            $table->index('app_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
