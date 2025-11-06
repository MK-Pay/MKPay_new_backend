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
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained('webhooks')->onDelete('cascade');
            $table->string('event');
            $table->string('url');
            $table->json('payload')->comment('Data sent to webhook');
            $table->unsignedSmallInteger('http_status')->nullable()->comment('HTTP response status code');
            $table->text('response_body')->nullable();
            $table->unsignedInteger('attempt')->default(1)->comment('Retry attempt number');
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable()->comment('Request duration in milliseconds');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index('webhook_id');
            $table->index('event');
            $table->index('success');
            $table->index('sent_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
