<?php

namespace App\Observers;

use App\Models\Role;
use App\Services\CacheService;
use Illuminate\Support\Facades\Log;

class RoleObserver
{
    protected CacheService $cacheService;
    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }
    public function created(Role $role): void
    {
        $this->invalidateRoleCache();
    }
    public function updated(Role $role): void
    {
        $this->invalidateRoleCache();
    }
    public function deleted(Role $role): void
    {
        $this->invalidateRoleCache();
    }
    public function restored(Role $role): void
    {
        $this->invalidateRoleCache();
    }
    public function forceDeleted(Role $role): void
    {
        $this->invalidateRoleCache();
    }
    private function invalidateRoleCache(): void
    {
        try {
            $tags = ['roles', 'reference_data', 'statistics'];
            $this->cacheService->flushByTags($tags);
        } catch (\Exception $e) {
            Log::error('Role cache invalidation failed: ' . $e->getMessage());
        }
    }
}
