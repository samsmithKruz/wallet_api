<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'balance',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'balance' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the wallet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions for the wallet.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get credit transactions.
     */
    public function creditTransactions()
    {
        return $this->transactions()->where('type', TransactionType::CREDIT);
    }

    /**
     * Get debit transactions.
     */
    public function debitTransactions()
    {
        return $this->transactions()->where('type', TransactionType::DEBIT);
    }

    /**
     * Get transfer_in transactions.
     */
    public function transferInTransactions()
    {
        return $this->transactions()->where('type', TransactionType::TRANSFER_IN);
    }

    /**
     * Get transfer_out transactions.
     */
    public function transferOutTransactions()
    {
        return $this->transactions()->where('type', TransactionType::TRANSFER_OUT);
    }

    /**
     * Check if wallet can be deleted (balance must be zero).
     */
    public function canBeDeleted(): bool
    {
        return $this->balance == 0;
    }

    /**
     * Check if wallet has sufficient balance.
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }



    /**
     * Get total credits.
     */
    public function getTotalCreditsAttribute(): float
    {
        return $this->transactions()
            ->where('type', TransactionType::CREDIT)
            ->where('status', TransactionStatus::COMPLETED)
            ->sum('amount') ?? 0;
    }

    /**
     * Get total debits.
     */
    public function getTotalDebitsAttribute(): float
    {
        return $this->transactions()
            ->where('type', TransactionType::DEBIT)
            ->where('status', TransactionStatus::COMPLETED)
            ->sum('amount') ?? 0;
    }

    /**
     * Get total transfers in.
     */
    public function getTotalTransfersInAttribute(): float
    {
        return $this->transactions()
            ->where('type', TransactionType::TRANSFER_IN)
            ->where('status', TransactionStatus::COMPLETED)
            ->sum('amount') ?? 0;
    }

    /**
     * Get total transfers out.
     */
    public function getTotalTransfersOutAttribute(): float
    {
        return $this->transactions()
            ->where('type', TransactionType::TRANSFER_OUT)
            ->where('status', TransactionStatus::COMPLETED)
            ->sum('amount') ?? 0;
    }
}
