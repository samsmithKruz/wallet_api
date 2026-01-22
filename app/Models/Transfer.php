<?php
// app/Models/Transfer.php

namespace App\Models;

use App\Enums\TransferStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transfer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'sender_wallet_id',
        'receiver_wallet_id',
        'amount',
        'reference',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'status' => TransferStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the sender wallet.
     */
    public function senderWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'sender_wallet_id');
    }

    /**
     * Get the receiver wallet.
     */
    public function receiverWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'receiver_wallet_id');
    }

    /**
     * Get all transactions associated with this transfer.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the sender transaction (transfer_out).
     */
    public function senderTransaction(): HasOne
    {
        return $this->hasOne(Transaction::class)->where('type', TransactionType::TRANSFER_OUT);
    }

    /**
     * Get the receiver transaction (transfer_in).
     */
    public function receiverTransaction(): HasOne
    {
        return $this->hasOne(Transaction::class)->where('type', TransactionType::TRANSFER_IN);
    }

    /**
     * Check if transfer is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === TransferStatus::COMPLETED;
    }

    /**
     * Check if transfer involves the given wallet.
     */
    public function involvesWallet(Wallet $wallet): bool
    {
        return $this->sender_wallet_id === $wallet->id ||
            $this->receiver_wallet_id === $wallet->id;
    }

    /**
     * Get the counterpart wallet for a given wallet.
     */
    public function counterpartWallet(Wallet $wallet): ?Wallet
    {
        if ($this->sender_wallet_id === $wallet->id) {
            return $this->receiverWallet;
        }

        if ($this->receiver_wallet_id === $wallet->id) {
            return $this->senderWallet;
        }

        return null;
    }
}
