<?php

namespace App\DTOs;

class CreateOrderDTO
{
    public function __construct(
        public readonly int $companyId,
        public readonly int $productId,
        public readonly int $quantity,
        public readonly string $deadline
    ) {}
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'deadline' => $this->deadline,
            'status' => 'wait',
        ];
    }
}