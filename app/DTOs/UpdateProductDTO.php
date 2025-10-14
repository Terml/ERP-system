<?php

namespace App\DTOs;

class UpdateProductDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $type = null,
        public readonly ?string $unit = null,
    ) {}
}
