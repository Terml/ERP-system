<?php

namespace App\Factories;

use App\DTOs\CreateOrderDTO;
use App\DTOs\UpdateOrderDTO;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;

class OrderDTOFactory
{
    public static function createFromRequest(CreateOrderRequest $request): CreateOrderDTO
    {
        $validated = $request->validated();
        return new CreateOrderDTO(
            companyId: $validated['company_id'],
            productId: $validated['product_id'],
            quantity: $validated['quantity'],
            deadline: $validated['deadline']
        );
    }

    public static function createUpdateFromRequest(UpdateOrderRequest $request): UpdateOrderDTO
    {
        $validated = $request->validated();
        
        return new UpdateOrderDTO(
            quantity: $validated['quantity'] ?? null,
            deadline: $validated['deadline'] ?? null
        );
    }
}