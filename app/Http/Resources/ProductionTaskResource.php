<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionTaskResource extends JsonResource
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
            'order' => [
                'id' => $this->order->id,
                'company_name' => $this->order->company->name,
                'product_name' => $this->order->product->name,
                'deadline' => $this->order->deadline,
            ],
            'user' => $this->when($this->user, [
                'id' => $this->user->id,
                'login' => $this->user->login,
            ]),
            'quantity' => $this->quantity,
            'status' => $this->status,
            'components' => TaskComponentResource::collection($this->whenLoaded('components')),
            'components_count' => $this->whenLoaded('components', function () {
                return $this->components->count();
            }),
            'taken_at' => $this->taken_at,
            'sent_for_inspection_at' => $this->sent_for_inspection_at,
            'accepted_at' => $this->accepted_at,
            'rejected_at' => $this->rejected_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
