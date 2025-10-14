<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\ProductService;
use App\Services\CacheService;
use App\DTOs\ProductDTOFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected ProductService $productService;
    protected CacheService $cacheService;
    public function __construct(ProductService $productService, CacheService $cacheService)
    {
        $this->productService = $productService;
        $this->cacheService = $cacheService;
    }
    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->getAllProducts($request);
        return response()->json($products);
    }
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProduct($id);
        if (!$product) {
            return response()->json(['message' => 'Продукт не найден'], 404);
        }
        
        return response()->json($product->toArray());
    }
    public function store(CreateProductRequest $request): JsonResponse
    {
        $dto = ProductDTOFactory::createFromRequest($request);
        $product = $this->productService->createProduct($dto);
        
        return response()->json($product->toArray(), 201);
    }
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $dto = ProductDTOFactory::createFromUpdateRequest($request);
        $product = $this->productService->updateProduct($id, $dto);
        
        if (!$product) {
            return response()->json(['message' => 'Продукт не найден'], 404);
        }
        return response()->json($product->toArray());
    }
    public function destroy(int $id): JsonResponse
    {
        $result = $this->productService->deleteProduct($id);
        if (!$result) {
            return response()->json(['message' => 'Продукт не найден'], 404);
        }
        return response()->json(['message' => 'Продукт успешно удален']);
    }
    public function statistics(): JsonResponse
    {
        $statistics = $this->productService->getProductStatistics();
        return response()->json($statistics);
    }
    public function select(): JsonResponse
    {
        $products = $this->productService->getProductsForSelect();
        return response()->json($products);
    }
    public function clearCache(): JsonResponse
    {
        $this->productService->clearProductCache();
        return response()->json(['message' => 'Кеш продуктов очищен']);
    }
    public function cacheInfo(): JsonResponse
    {
        $cacheKeys = [
            'products:all',
            'products:type:product',
            'products:type:material',
            'products:statistics',
            'products:select',
        ];
        $cacheInfo = [];
        foreach ($cacheKeys as $key) {
            $cacheInfo[$key] = [
                'exists' => $this->cacheService->has($key),
                'ttl' => $this->cacheService->ttl($key),
            ];
        }
        return response()->json($cacheInfo);
    }
}
