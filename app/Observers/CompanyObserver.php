<?php

namespace App\Observers;

use App\Models\Company;
use App\Services\CacheService;
use Illuminate\Support\Facades\Log;

class CompanyObserver
{
    protected CacheService $cacheService;
    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }
    public function created(Company $company): void
    {
        $this->invalidateCompanyCache();
    }
    public function updated(Company $company): void
    {
        $this->invalidateCompanyCache();
    }
    public function deleted(Company $company): void
    {
        $this->invalidateCompanyCache();
    }
    public function restored(Company $company): void
    {
        $this->invalidateCompanyCache();
    }
    public function forceDeleted(Company $company): void
    {
        $this->invalidateCompanyCache();
    }
    private function invalidateCompanyCache(): void
    {
        try {
            $tags = ['companies', 'reference_data', 'statistics'];
            $this->cacheService->flushByTags($tags);
        } catch (\Exception $e) {
            Log::error('Ошибка инвалидации кеша ' . $e->getMessage());
        }
    }
}
