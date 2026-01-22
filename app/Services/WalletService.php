<?php
// app/Services/WalletService.php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    /**
     * Fund a wallet (credit transaction)
     * 
     * @param int $walletId
     * @param float $amount
     * @param string|null $description
     * @return array ['transaction' => Transaction, 'new_balance' => float]
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Exception
     */
    public function fund(int $walletId, float $amount, ?string $description = null): array
    {
        return DB::transaction(function () use ($walletId, $amount, $description) {
            // Lock the wallet for update to prevent race conditions
            $wallet = Wallet::lockForUpdate()->findOrFail($walletId);
            
            $description = $description ?? 'Wallet funding';
            
            // Create credit transaction
            $transaction = $this->createTransaction(
                walletId: $walletId,
                type: TransactionType::CREDIT,
                amount: $amount,
                description: $description
            );
            
            // Update wallet balance
            $wallet->increment('balance', $amount);
            
            // Refresh wallet to get updated balance
            $wallet->refresh();
            
            return [
                'transaction' => $transaction,
                'new_balance' => (float) $wallet->balance,
                'wallet' => $wallet,
            ];
        });
    }
    
    /**
     * Withdraw from a wallet (debit transaction)
     * 
     * @param int $walletId
     * @param float $amount
     * @param string|null $description
     * @return array ['transaction' => Transaction, 'new_balance' => float]
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Exception If insufficient balance
     */
    public function withdraw(int $walletId, float $amount, ?string $description = null): array
    {
        return DB::transaction(function () use ($walletId, $amount, $description) {
            // Lock the wallet for update to prevent race conditions
            $wallet = Wallet::lockForUpdate()->findOrFail($walletId);
            
            $description = $description ?? 'Wallet withdrawal';
            
            // Check if wallet has sufficient balance
            if (!$wallet->hasSufficientBalance($amount)) {
                throw new \Exception('Insufficient balance', 400);
            }
            
            // Create debit transaction
            $transaction = $this->createTransaction(
                walletId: $walletId,
                type: TransactionType::DEBIT,
                amount: $amount,
                description: $description
            );
            
            // Update wallet balance
            $wallet->decrement('balance', $amount);
            
            // Refresh wallet to get updated balance
            $wallet->refresh();
            
            return [
                'transaction' => $transaction,
                'new_balance' => (float) $wallet->balance,
                'wallet' => $wallet,
            ];
        });
    }
    
    /**
     * Create a standardized transaction
     * 
     * @param int $walletId
     * @param TransactionType $type
     * @param float $amount
     * @param string|null $description
     * @return Transaction
     */
    public function createTransaction(int $walletId, TransactionType $type, float $amount, ?string $description = null): Transaction
    {
        return Transaction::create([
            'wallet_id' => $walletId,
            'type' => $type,
            'amount' => $amount,
            'reference' => Str::uuid(),
            'description' => $description ?? $this->getDefaultDescription($type),
            'status' => TransactionStatus::COMPLETED,
        ]);
    }
    
    /**
     * Get default description based on transaction type
     * 
     * @param TransactionType $type
     * @return string
     */
    private function getDefaultDescription(TransactionType $type): string
    {
        return match ($type) {
            TransactionType::CREDIT => 'Credit transaction',
            TransactionType::DEBIT => 'Debit transaction',
            TransactionType::TRANSFER_IN => 'Money received',
            TransactionType::TRANSFER_OUT => 'Money sent',
        };
    }
    
    /**
     * Check if wallet can perform an operation with amount
     * 
     * @param int $walletId
     * @param float $amount
     * @param TransactionType $type
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function canPerformOperation(int $walletId, float $amount, TransactionType $type): bool
    {
        $wallet = Wallet::findOrFail($walletId);
        
        if ($type->isOutgoing()) {
            return $wallet->hasSufficientBalance($amount);
        }
        
        return true; // For incoming transactions, always allowed
    }
    
    /**
     * Get wallet with locked balance for thread-safe operations
     * 
     * @param int $walletId
     * @return Wallet
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getWalletWithLock(int $walletId): Wallet
    {
        return Wallet::lockForUpdate()->findOrFail($walletId);
    }
    
    /**
     * Update wallet balance with lock
     * 
     * @param int $walletId
     * @param float $amount Positive to add, negative to subtract
     * @param string $operationType For logging/auditing
     * @return Wallet
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Exception If resulting balance would be negative
     */
    public function updateBalance(int $walletId, float $amount, string $operationType = 'balance_update'): Wallet
    {
        return DB::transaction(function () use ($walletId, $amount, $operationType) {
            $wallet = Wallet::lockForUpdate()->findOrFail($walletId);
            
            // Check if the operation would result in negative balance
            $newBalance = $wallet->balance + $amount;
            if ($newBalance < 0) {
                throw new \Exception("Operation would result in negative balance: {$operationType}", 400);
            }
            
            // Update balance
            if ($amount >= 0) {
                $wallet->increment('balance', $amount);
            } else {
                $wallet->decrement('balance', abs($amount));
            }
            
            return $wallet->refresh();
        });
    }
}