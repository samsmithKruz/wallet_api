<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->foreignId('receiver_wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->uuid('reference')->unique();
            $table->string('status')->default('completed')->comment('pending, completed, failed');
            $table->timestamps();

            $table->index('sender_wallet_id');
            $table->index('receiver_wallet_id');
            $table->index('reference');
            $table->index('status');
            $table->index('created_at');

            $table->index(['sender_wallet_id', 'created_at']);
            $table->index(['receiver_wallet_id', 'created_at']);
            $table->index(['sender_wallet_id', 'receiver_wallet_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
