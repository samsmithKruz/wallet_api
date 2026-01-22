<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TransactionStatuses;
use TransactionTypes;

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
        return $this->type === TransactionTypes::CREDIT->value;
    }

    /**
     * Check if transaction is a debit.
     */
    public function isDebit(): bool
    {
        return $this->type === TransactionTypes::DEBIT->value;
    }

    /**
     * Check if transaction is part of a transfer.
     */
    public function isTransfer(): bool
    {
        return in_array($this->type, [TransactionTypes::TRANSFER_IN->value, TransactionTypes::TRANSFER_OUT->value]);
    }

    /**
     * Check if transaction is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === TransactionStatuses::COMPLETED->value;
    }
}
