<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\CacheService;

class ProductObserver
{
    protected CacheService $cacheService;
    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }
    public function created(Product $product): void
    {
        $this->invalidateProductCache();
    }
    public function updated(Product $product): void
    {
        $this->invalidateProductCache($product->id);
    }
    public function deleted(Product $product): void
    {
        $this->invalidateProductCache($product->id);
    }
    public function restored(Product $product): void
    {
        $this->invalidateProductCache($product->id);
    }
    public function forceDeleted(Product $product): void
    {
        $this->invalidateProductCache($product->id);
    }
    protected function invalidateProductCache(?int $productId = null): void
    {
        if ($productId) {
            $this->cacheService->forget("product:{$productId}");
        }
        $this->cacheService->flushPattern('products:*');
    }
}
