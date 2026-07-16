<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'description' => $this->description,
            'amount'      => (float) $this->amount,
            'paid_by'     => new UserResource($this->whenLoaded('payer')),
            'shares'      => $this->whenLoaded('shares', fn() => $this->shares->map(fn($share) => [
                'user'   => new UserResource($share->user),
                'amount' => (float) $share->share_amount,
            ])),
            'created_at'  => $this->created_at->toDateTimeString(),
        ];
    }
}