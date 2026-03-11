<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'set' => new SetResource($this->whenLoaded('set')),
            'purchase_price' => $this->purchase_price !== null ? (float) $this->purchase_price : null,
            'purchase_date' => $this->purchase_date?->format('Y-m-d'),
            'condition' => $this->condition,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
