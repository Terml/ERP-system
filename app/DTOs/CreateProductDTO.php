<?php

namespace App\DTOs;

class CreateProductDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $unit,
    ) {}
}
