<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\CacheService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    protected CacheService $cacheService;
    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }
    public function created(Order $order): void
    {
        $this->invalidateOrderCache();
    }
    public function updated(Order $order): void
    {
        $this->invalidateOrderCache();
    }
    public function deleted(Order $order): void
    {
        $this->invalidateOrderCache();
    }
    public function restored(Order $order): void
    {
        $this->invalidateOrderCache();
    }
    public function forceDeleted(Order $order): void
    {
        $this->invalidateOrderCache();
    }
    private function invalidateOrderCache(): void
    {
        try {
            $tags = ['orders', 'statistics'];
            $this->cacheService->flushByTags($tags);
        } catch (\Exception $e) {
            Log::error('Ошибка инвалидации кеша ' . $e->getMessage());
        }
    }
}
