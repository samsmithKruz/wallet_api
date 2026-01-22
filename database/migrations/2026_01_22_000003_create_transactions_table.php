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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('type')->comment('credit, debit, transfer_in, transfer_out');
            $table->decimal('amount', 15, 2);
            $table->uuid('reference')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('completed')->comment('pending, completed, failed');
            $table->foreignId('transfer_id')->nullable(); // REMOVED: ->constrained()->nullOnDelete()
            $table->timestamps();

            // Indexes for performance
            $table->index('wallet_id');
            $table->index('type');
            $table->index('status');
            $table->index('reference');
            $table->index('transfer_id');
            $table->index('created_at');

            // Composite index for common queries
            $table->index(['wallet_id', 'type']);
            $table->index(['wallet_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
