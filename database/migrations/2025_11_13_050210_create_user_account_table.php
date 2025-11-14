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
        Schema::create('user_account', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('public.accounts')->onDelete('cascade');
            $table->boolean('is_root')->default(false)->comment('Root user is the master/owner of the account');
            $table->timestamps();

            $table->unique(['user_id', 'account_id']);
            $table->index('user_id');
            $table->index('account_id');
            $table->index('is_root');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_account');
    }
};
