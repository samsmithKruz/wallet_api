<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\TransferStatus;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    /**
     * Initiate a transfer between wallets
     * 
     * @param int $senderWalletId
     * @param int $receiverWalletId
     * @param float $amount
     * @param string|null $description
     * @return array ['transfer' => Transfer, 'sender_transaction' => Transaction, 'receiver_transaction' => Transaction]
     * @throws \Exception
     */
    public function initiateTransfer(int $senderWalletId, int $receiverWalletId, float $amount, ?string $description = null): array
    {
        return DB::transaction(function () use ($senderWalletId, $receiverWalletId, $amount, $description) {
            // Validate wallets exist and get them with lock
            $wallets = $this->getAndLockWallets($senderWalletId, $receiverWalletId);

            $senderWallet = $wallets['sender'];
            $receiverWallet = $wallets['receiver'];

            // Validate transfer
            $this->validateTransfer($senderWallet, $receiverWallet, $amount);

            $description = $description ?? 'Money transfer';

            // Create transfer record
            $transfer = $this->createTransfer(
                senderWalletId: $senderWalletId,
                receiverWalletId: $receiverWalletId,
                amount: $amount,
                description: $description
            );

            // Create transactions
            $senderTransaction = $this->createTransferTransaction(
                walletId: $senderWalletId,
                transferId: $transfer->id,
                type: TransactionType::TRANSFER_OUT,
                amount: $amount,
                description: $description
            );

            $receiverTransaction = $this->createTransferTransaction(
                walletId: $receiverWalletId,
                transferId: $transfer->id,
                type: TransactionType::TRANSFER_IN,
                amount: $amount,
                description: $description
            );

            // Update wallet balances
            $this->updateBalancesForTransfer($senderWallet, $receiverWallet, $amount);

            // Load relationships
            $transfer->load(['senderWallet.user', 'receiverWallet.user']);
            $transfer->sender_transaction = $senderTransaction;
            $transfer->receiver_transaction = $receiverTransaction;

            return [
                'transfer' => $transfer,
                'sender_transaction' => $senderTransaction,
                'receiver_transaction' => $receiverTransaction,
                'sender_wallet' => $senderWallet->refresh(),
                'receiver_wallet' => $receiverWallet->refresh(),
            ];
        });
    }

    /**
     * Get and lock wallets for transfer (prevents deadlocks by ordering)
     * 
     * @param int $senderWalletId
     * @param int $receiverWalletId
     * @return array
     * @throws \Exception
     */
    private function getAndLockWallets(int $senderWalletId, int $receiverWalletId): array
    {
        // Lock wallets in consistent order to prevent deadlocks
        $wallets = Wallet::whereIn('id', [$senderWalletId, $receiverWalletId])
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $senderWallet = $wallets->firstWhere('id', $senderWalletId);
        $receiverWallet = $wallets->firstWhere('id', $receiverWalletId);

        if (!$senderWallet) {
            throw new \Exception('Sender wallet not found', 404);
        }

        if (!$receiverWallet) {
            throw new \Exception('Receiver wallet not found', 404);
        }

        return [
            'sender' => $senderWallet,
            'receiver' => $receiverWallet,
        ];
    }

    /**
     * Validate transfer parameters
     * 
     * @param Wallet $senderWallet
     * @param Wallet $receiverWallet
     * @param float $amount
     * @return void
     * @throws \Exception
     */
    private function validateTransfer(Wallet $senderWallet, Wallet $receiverWallet, float $amount): void
    {
        // Check sender and receiver are different
        if ($senderWallet->id === $receiverWallet->id) {
            throw new \Exception('Sender and receiver cannot be the same', 422);
        }

        // Check if sender has sufficient balance
        if (!$senderWallet->hasSufficientBalance($amount)) {
            throw new \Exception('Insufficient balance', 400);
        }

        // Validate amount is positive
        if ($amount <= 0) {
            throw new \Exception('Amount must be greater than 0', 422);
        }
    }

    /**
     * Create transfer record
     * 
     * @param int $senderWalletId
     * @param int $receiverWalletId
     * @param float $amount
     * @param string $description
     * @return Transfer
     */
    private function createTransfer(int $senderWalletId, int $receiverWalletId, float $amount, string $description): Transfer
    {
        return Transfer::create([
            'sender_wallet_id' => $senderWalletId,
            'receiver_wallet_id' => $receiverWalletId,
            'amount' => $amount,
            'reference' => Str::uuid(),
            'status' => TransferStatus::COMPLETED,
        ]);
    }

    /**
     * Create transaction for transfer
     * 
     * @param int $walletId
     * @param int $transferId
     * @param TransactionType $type
     * @param float $amount
     * @param string $description
     * @return Transaction
     */
    private function createTransferTransaction(int $walletId, int $transferId, TransactionType $type, float $amount, string $description): Transaction
    {
        return Transaction::create([
            'wallet_id' => $walletId,
            'transfer_id' => $transferId,
            'type' => $type,
            'amount' => $amount,
            'reference' => Str::uuid(),
            'description' => $description,
            'status' => TransactionStatus::COMPLETED,
        ]);
    }

    /**
     * Update wallet balances for transfer
     * 
     * @param Wallet $senderWallet
     * @param Wallet $receiverWallet
     * @param float $amount
     * @return void
     */
    private function updateBalancesForTransfer(Wallet $senderWallet, Wallet $receiverWallet, float $amount): void
    {
        $senderWallet->decrement('balance', $amount);
        $receiverWallet->increment('balance', $amount);
    }

    /**
     * Get transfer with details
     * 
     * @param int $transferId
     * @return Transfer
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getTransferWithDetails(int $transferId): Transfer
    {
        return Transfer::with([
            'senderWallet.user',
            'receiverWallet.user',
            'transactions'
        ])->findOrFail($transferId);
    }

    /**
     * Get wallet transfers with pagination and filtering
     * 
     * @param int $walletId
     * @param string $type
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getWalletTransfers(int $walletId, string $type = 'all', int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        // First, verify wallet exists
        $wallet = Wallet::findOrFail($walletId);

        $query = Transfer::with([
            'senderWallet.user',
            'receiverWallet.user',
            'transactions'
        ]);

        // Apply filters based on wallet involvement
        if ($type === 'incoming') {
            $query->where('receiver_wallet_id', $walletId);
        } elseif ($type === 'outgoing') {
            $query->where('sender_wallet_id', $walletId);
        } else {
            $query->where(function ($q) use ($walletId) {
                $q->where('sender_wallet_id', $walletId)
                    ->orWhere('receiver_wallet_id', $walletId);
            });
        }

        // Order by latest first
        $query->orderBy('created_at', 'desc');

        $transfers = $query->paginate($perPage);

        // Add metadata about transfer direction
        $transfers->getCollection()->transform(function ($transfer) use ($walletId) {
            $transfer->direction = $transfer->sender_wallet_id == $walletId ? 'outgoing' : 'incoming';
            $transfer->counterpart = $transfer->sender_wallet_id == $walletId
                ? $transfer->receiverWallet->user->name
                : $transfer->senderWallet->user->name;
            return $transfer;
        });

        return $transfers;
    }

    /**
     * Get transfer summary for wallet
     * 
     * @param int $walletId
     * @return array
     */
    public function getTransferSummary(int $walletId): array
    {
        return [
            'incoming_count' => Transfer::where('receiver_wallet_id', $walletId)->count(),
            'outgoing_count' => Transfer::where('sender_wallet_id', $walletId)->count(),
            'total_transfers' => Transfer::where('sender_wallet_id', $walletId)
                ->orWhere('receiver_wallet_id', $walletId)
                ->count(),
        ];
    }
}
