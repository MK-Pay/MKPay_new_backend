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
        Schema::create('document_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->string('document_type', 20)->comment('CPF or CNPJ');
            $table->string('document_number', 14);
            $table->string('status', 50)->comment('pending, validated, rejected');
            $table->json('validation_data')->nullable()->comment('Data from validation service');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('validated_by')->nullable()->comment('Staff user ID who validated/rejected');
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('account_id');
            $table->index('document_type');
            $table->index('document_number');
            $table->index('status');
            $table->index('validated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_validations');
    }
};
