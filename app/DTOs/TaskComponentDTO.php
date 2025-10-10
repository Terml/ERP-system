<?php

namespace App\DTOs;

class TaskComponentDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly int $quantity
    ) {}
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
        ];
    }
}