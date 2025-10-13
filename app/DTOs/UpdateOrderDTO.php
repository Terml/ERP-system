<?php

namespace App\DTOs;

class UpdateOrderDTO
{
    public function __construct(
        public readonly ?int $quantity = null,
        public readonly ?string $deadline = null
    ) {}
    public function toArray(): array
    {
        return array_filter([
            'quantity' => $this->quantity,
            'deadline' => $this->deadline,
        ], fn($value) => $value !== null);
    }
}
