<?php

namespace App\Models;

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
        return $this->transactions()->where('type', 'credit');
    }

    /**
     * Get debit transactions.
     */
    public function debitTransactions()
    {
        return $this->transactions()->where('type', 'debit');
    }

    /**
     * Get transfer_in transactions.
     */
    public function transferInTransactions()
    {
        return $this->transactions()->where('type', 'transfer_in');
    }

    /**
     * Get transfer_out transactions.
     */
    public function transferOutTransactions()
    {
        return $this->transactions()->where('type', 'transfer_out');
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
}
