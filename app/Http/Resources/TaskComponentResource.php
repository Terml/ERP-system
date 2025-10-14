<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskComponentResource extends JsonResource
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
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'type' => $this->product->type,
                'unit' => $this->product->unit,
            ],
            'quantity' => $this->quantity,
            'used_quantity' => $this->used_quantity,
            'created_at' => $this->created_at,
        ];
    }
}
