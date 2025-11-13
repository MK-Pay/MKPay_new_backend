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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('account_type_id')->constrained('account_types')->onDelete('restrict');
            $table->foreignId('account_category_id')->nullable()->constrained('account_categories')->onDelete('restrict');
            $table->foreignId('account_status_id')->constrained('account_status')->onDelete('restrict');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('name');
            $table->string('cpf', 11)->nullable()->unique()->comment('CPF for PF accounts');
            $table->string('cnpj', 14)->nullable()->unique()->comment('CNPJ for PJ accounts');
            $table->string('phone', 20)->nullable();
            $table->json('usage_types')->nullable()->comment('Digital products, physical products, services, other');
            $table->decimal('hourly_transaction_limit', 15, 2)->nullable()->comment('Hourly transaction limit for pending accounts');
            $table->decimal('daily_transaction_limit', 15, 2)->nullable()->comment('Daily transaction limit for pending accounts');
            $table->timestamp('verified_at')->nullable()->comment('When account was fully verified');
            $table->timestamps();
            $table->softDeletes();

            $table->index('uuid');
            $table->index('email');
            $table->index('cpf');
            $table->index('cnpj');
            $table->index('account_type_id');
            $table->index('account_category_id');
            $table->index('account_status_id');
            $table->index('verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
