<?php

namespace App\DTOs;

class UpdateOrderDTO
{
    public function __construct(
        public readonly ?int $quantity = null,
        public readonly ?string $deadline = null,
        public readonly ?string $priority = null,
        public readonly ?string $notes = null
    ) {}
    public function toArray(): array
    {
        return array_filter([
            'quantity' => $this->quantity,
            'deadline' => $this->deadline,
            'priority' => $this->priority,
            'notes' => $this->notes,
        ], fn($value) => $value !== null);
    }
}
