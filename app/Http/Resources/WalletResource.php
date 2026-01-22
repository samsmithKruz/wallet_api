<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
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
            'user_id' => $this->user_id,
            'balance' => (float) $this->balance,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'transaction_summary' => [
                'total_credits' => (float) $this->total_credits,
                'total_debits' => (float) $this->total_debits,
                'total_transfers_in' => (float) $this->total_transfers_in,
                'total_transfers_out' => (float) $this->total_transfers_out,
                'net_flow' => (float) ($this->total_credits + $this->total_transfers_in - $this->total_debits - $this->total_transfers_out),
            ],
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
