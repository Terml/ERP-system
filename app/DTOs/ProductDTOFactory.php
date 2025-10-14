<?php

namespace App\DTOs;

use App\Models\Product;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;

class ProductDTOFactory
{
    public static function createFromModel(Product $product): ProductDTO
    {
        return new ProductDTO(
            id: $product->id,
            name: $product->name,
            type: $product->type,
            unit: $product->unit,
            createdAt: $product->created_at->toISOString(),
            updatedAt: $product->updated_at->toISOString(),
        );
    }
    public static function createFromRequest(CreateProductRequest $request): CreateProductDTO
    {
        return new CreateProductDTO(
            name: $request->validated()['name'],
            type: $request->validated()['type'],
            unit: $request->validated()['unit'],
        );
    }
    public static function createFromUpdateRequest(UpdateProductRequest $request): UpdateProductDTO
    {
        $validated = $request->validated();
        return new UpdateProductDTO(
            name: $validated['name'] ?? null,
            type: $validated['type'] ?? null,
            unit: $validated['unit'] ?? null,
        );
    }
}
