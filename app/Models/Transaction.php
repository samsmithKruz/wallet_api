<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'reference',
        'description',
        'status',
        'transfer_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
        'created_at' => 'datetime',
    ];

    /**
     * Get the wallet that owns the transaction.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the transfer that this transaction belongs to (if any).
     */
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }

    /**
     * Check if transaction is a credit.
     */
    public function isCredit(): bool
    {
        return $this->type === TransactionType::CREDIT;
    }

    /**
     * Check if transaction is a debit.
     */
    public function isDebit(): bool
    {
        return $this->type === TransactionType::DEBIT;
    }

    /**
     * Check if transaction is part of a transfer.
     */
    public function isTransfer(): bool
    {
        return $this->type->isTransfer();
    }

    /**
     * Check if transaction is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === TransactionStatus::COMPLETED;
    }

    /**
     * Scope for credit transactions.
     */
    public function scopeCredit($query)
    {
        return $query->where('type', TransactionType::CREDIT);
    }

    /**
     * Scope for debit transactions.
     */
    public function scopeDebit($query)
    {
        return $query->where('type', TransactionType::DEBIT);
    }

    /**
     * Scope for transfer_in transactions.
     */
    public function scopeTransferIn($query)
    {
        return $query->where('type', TransactionType::TRANSFER_IN);
    }

    /**
     * Scope for transfer_out transactions.
     */
    public function scopeTransferOut($query)
    {
        return $query->where('type', TransactionType::TRANSFER_OUT);
    }
}
