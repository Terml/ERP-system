<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'company' => [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ],
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'type' => $this->product->type,
                'unit' => $this->product->unit,
            ],
            'quantity' => $this->quantity,
            'deadline' => $this->deadline,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'tasks_count' => $this->whenLoaded('productionTasks', function () {
                return $this->productionTasks->count();
            }),
            'completed_tasks_count' => $this->whenLoaded('productionTasks', function () {
                return $this->productionTasks->where('status', 'completed')->count();
            }),
        ];
    }
}
