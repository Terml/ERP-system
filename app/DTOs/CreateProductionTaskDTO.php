<?php

namespace App\DTOs;

class CreateProductionTaskDTO
{
    public function __construct(
        public readonly int $orderId,
        public readonly ?int $userId = null
    ) {}
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'user_id' => $this->userId,
            'status' => 'wait',
        ];
    }
}