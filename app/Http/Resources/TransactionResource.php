<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wallet_id' => $this->wallet_id,
            'type' => $this->type->value,
            'amount' => (float) $this->amount,
            'reference' => $this->reference,
            'description' => $this->description,
            'status' => $this->status->value,
            'transfer_id' => $this->transfer_id,
            'created_at' => $this->created_at->toISOString(),
            'wallet' => [
                'id' => $this->wallet->id,
                'user_id' => $this->wallet->user_id,
                'balance' => (float) $this->wallet->balance,
            ],

        ];
    }
}
