<?php

namespace App\DTOs;

class CreateProductionTaskDTO
{
    public function __construct(
        public readonly int $orderId,
        public readonly int $quantity,
        public readonly ?int $userId = null
    ) {}
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'quantity' => $this->quantity,
            'user_id' => $this->userId,
            'status' => 'wait',
        ];
    }
}