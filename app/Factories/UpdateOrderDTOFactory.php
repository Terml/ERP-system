<?php

namespace App\Factories;

use App\DTOs\UpdateOrderDTO;
use App\Http\Requests\UpdateOrderRequest;

class UpdateOrderDTOFactory
{
    public static function createFromRequest(UpdateOrderRequest $request): UpdateOrderDTO
    {
        $validated = $request->validated();
        return new UpdateOrderDTO(
            quantity: $validated['quantity'] ?? null,
            deadline: $validated['deadline'] ?? null,
        );
    }
}
