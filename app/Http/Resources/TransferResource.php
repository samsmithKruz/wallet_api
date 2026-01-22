<?php

namespace App\Http\Resources;

use App\Enums\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get transactions
        $senderTransaction = $this->transactions
            ->where('type', TransactionType::TRANSFER_OUT)
            ->first();

        $receiverTransaction = $this->transactions
            ->where('type', TransactionType::TRANSFER_IN)
            ->first();

        return [
            'id' => $this->id,
            'sender_wallet_id' => $this->sender_wallet_id,
            'receiver_wallet_id' => $this->receiver_wallet_id,
            'amount' => (float) $this->amount,
            'reference' => $this->reference,
            'status' => $this->status->value,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            // Add direction and counterpart for wallet transfers view
            'direction' => $this->direction ?? null,
            'counterpart' => $this->counterpart ?? null,
            'sender' => [
                'wallet_id' => $this->sender_wallet_id,
                'user_id' => $this->senderWallet->user_id,
                'user_name' => $this->senderWallet->user->name,
                'user_email' => $this->senderWallet->user->email,
            ],
            'receiver' => [
                'wallet_id' => $this->receiver_wallet_id,
                'user_id' => $this->receiverWallet->user_id,
                'user_name' => $this->receiverWallet->user->name,
                'user_email' => $this->receiverWallet->user->email,
            ],
            'transactions' => [
                'sender_transaction' => $senderTransaction ? [
                    'id' => $senderTransaction->id,
                    'type' => $senderTransaction->type->value,
                    'amount' => (float) $senderTransaction->amount,
                    'reference' => $senderTransaction->reference,
                    'description' => $senderTransaction->description,
                    'status' => $senderTransaction->status->value,
                    'created_at' => $senderTransaction->created_at->toISOString(),
                ] : null,
                'receiver_transaction' => $receiverTransaction ? [
                    'id' => $receiverTransaction->id,
                    'type' => $receiverTransaction->type->value,
                    'amount' => (float) $receiverTransaction->amount,
                    'reference' => $receiverTransaction->reference,
                    'description' => $receiverTransaction->description,
                    'status' => $receiverTransaction->status->value,
                    'created_at' => $receiverTransaction->created_at->toISOString(),
                ] : null,
            ],
        ];
    }
}
