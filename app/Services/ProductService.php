<?php

namespace App\Services;

use App\Models\Product;
use App\DTOs\ProductDTO;
use App\DTOs\CreateProductDTO;
use App\DTOs\UpdateProductDTO;
use App\DTOs\ProductDTOFactory;
use App\Services\CacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    protected Product $model;
    protected CacheService $cacheService;
    protected int $cacheTtl = 1800;
    public function __construct(Product $product, CacheService $cacheService)
    {
        $this->model = $product;
        $this->cacheService = $cacheService;
    }
    public function getAllProducts($request = null)
    {
        $query = $this->model->orderBy('name');
        if ($request) {
            if ($request->has('type') && $request->input('type') !== '') {
                $query->where('type', $request->input('type'));
            }
            if ($request->has('search') && $request->input('search') !== '') {
                $search = $request->input('search');
                $query->where('name', 'ilike', "%{$search}%");
            }
        }
        return $query->paginate(15);
    }
    public function getProductsByType(string $type): Collection
    {
        $cacheKey = "products:type:{$type}";
        return $this->cacheService->remember($cacheKey, function () use ($type) {
            return $this->model->where('type', $type)
                ->orderBy('name')
                ->get();
        }, $this->cacheTtl);
    }
    public function getProduct(int $id): ?ProductDTO
    {
        $cacheKey = "product:{$id}";
        $product = $this->cacheService->remember($cacheKey, function () use ($id) {
            return $this->model->find($id);
        }, $this->cacheTtl);
        return $product ? ProductDTOFactory::createFromModel($product) : null;
    }
    public function searchProducts(string $query): Collection
    {
        $cacheKey = 'products:search:' . md5($query);
        
        return $this->cacheService->remember($cacheKey, function () use ($query) {
            return $this->model->where('name', 'ILIKE', "%{$query}%")
                ->orderBy('name')
                ->get();
        }, 600);
    }
    public function getProductStatistics(): array
    {
        $cacheKey = 'products:statistics';
        return $this->cacheService->remember($cacheKey, function () {
            return [
                'total_products' => $this->model->count(),
                'products_by_type' => $this->model->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
                'recent_products' => $this->model->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(fn($product) => ProductDTOFactory::createFromModel($product))
                    ->toArray(),
            ];
        }, $this->cacheTtl);
    }
    public function createProduct(CreateProductDTO $dto): ProductDTO
    {
        $product = DB::transaction(function () use ($dto) {
            return $this->model->create([
                'name' => $dto->name,
                'type' => $dto->type,
                'unit' => $dto->unit,
            ]);
        });
        $this->invalidateProductCache();
        return ProductDTOFactory::createFromModel($product);
    }
    public function updateProduct(int $id, UpdateProductDTO $dto): ?ProductDTO
    {
        $product = DB::transaction(function () use ($id, $dto) {
            $product = $this->model->find($id);
            if (!$product) {
                return null;
            }
            $updateData = [];
            if ($dto->name !== null) {
                $updateData['name'] = $dto->name;
            }
            if ($dto->type !== null) {
                $updateData['type'] = $dto->type;
            }
            if ($dto->unit !== null) {
                $updateData['unit'] = $dto->unit;
            }
            $product->update($updateData);
            return $product;
        });
        if (!$product) {
            return null;
        }
        $this->invalidateProductCache($id);
        return ProductDTOFactory::createFromModel($product);
    }
    public function deleteProduct(int $id): bool
    {
        $result = DB::transaction(function () use ($id) {
            $product = $this->model->find($id);
            if (!$product) {
                return false;
            }
            return $product->delete();
        });
        if ($result) {
            $this->invalidateProductCache($id);
        }
        return $result;
    }
    protected function invalidateProductCache(?int $productId = null): void
    {
        if ($productId) {
            $this->cacheService->forget("product:{$productId}");
        }
        $this->cacheService->flushPattern('products:*');
    }
    public function clearProductCache(): void
    {
        $this->cacheService->flushPattern('products:*');
        $this->cacheService->flushPattern('product:*');
    }
    public function getProductsForSelect(): array
    {
        $cacheKey = 'products:select';
        return $this->cacheService->remember($cacheKey, function () {
            return $this->model->select('id', 'name', 'type', 'unit')
                ->orderBy('name')
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'type' => $product->type,
                        'unit' => $product->unit,
                    ];
                })
                ->toArray();
        }, $this->cacheTtl);
    }
}