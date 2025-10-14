<?php

namespace App\Services;

use App\Models\Company;
use App\Services\CacheService;
use Illuminate\Database\Eloquent\Collection;

class CompanyService extends BaseService
{
    protected CacheService $cacheService;
    protected int $cacheTtl = 3600; 
    public function __construct(Company $company, CacheService $cacheService)
    {
        parent::__construct($company);
        $this->cacheService = $cacheService;
    }
    public function getAllCompanies(): Collection
    {
        $cacheKey = 'companies:all';
        $tags = ['companies', 'reference_data'];
        return $this->cacheService->rememberWithTags($cacheKey, $tags, function () {
            return $this->model->orderBy('name')->get();
        }, $this->cacheTtl);
    }
    public function getCompany(int $id): ?Company
    {
        $cacheKey = "company:{$id}";
        $tags = ['companies', 'reference_data'];
        return $this->cacheService->rememberWithTags($cacheKey, $tags, function () use ($id) {
            return $this->model->find($id);
        }, $this->cacheTtl);
    }
    public function getCompaniesForSelect(): array
    {
        $cacheKey = 'companies:select';
        $tags = ['companies', 'reference_data', 'select'];
        return $this->cacheService->rememberWithTags($cacheKey, $tags, function () {
            return $this->model->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->map(function ($company) {
                    return [
                        'value' => $company->id,
                        'label' => $company->name,
                    ];
                })
                ->toArray();
        }, $this->cacheTtl);
    }
    public function searchCompanies(string $query): Collection
    {
        $cacheKey = 'companies:search:' . md5($query);
        $tags = ['companies', 'reference_data', 'search'];
        return $this->cacheService->rememberWithTags($cacheKey, $tags, function () use ($query) {
            return $this->model->where('name', 'ILIKE', "%{$query}%")
                ->orderBy('name')
                ->get();
        }, 600);
    }
    public function getCompaniesWithOrderCount(): Collection
    {
        $cacheKey = 'companies:with_orders';
        $tags = ['companies', 'reference_data', 'statistics'];
        return $this->cacheService->rememberWithTags($cacheKey, $tags, function () {
            return $this->model->withCount('orders')
                ->orderBy('name')
                ->get();
        }, $this->cacheTtl);
    }
    public function getCompanyStatistics(): array
    {
        $cacheKey = 'companies:statistics';
        $tags = ['companies', 'statistics'];
        return $this->cacheService->rememberWithTags($cacheKey, $tags, function () {
            $companies = $this->model->withCount('orders')->get();
            return [
                'total_companies' => $companies->count(),
                'companies_with_orders' => $companies->where('orders_count', '>', 0)->count(),
                'companies_without_orders' => $companies->where('orders_count', '=', 0)->count(),
                'top_companies' => $companies->sortByDesc('orders_count')
                    ->take(5)
                    ->map(function ($company) {
                        return [
                            'id' => $company->id,
                            'name' => $company->name,
                            'orders_count' => $company->orders_count,
                        ];
                    })
                    ->values()
                    ->toArray(),
                'recent_companies' => $this->model->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($company) {
                        return [
                            'id' => $company->id,
                            'name' => $company->name,
                            'created_at' => $company->created_at->format('Y-m-d H:i:s'),
                        ];
                    })
                    ->toArray(),
            ];
        }, $this->cacheTtl);
    }
    public function getCompanyByName(string $name): ?Company
    {
        $cacheKey = "company:name:" . md5($name);
        $tags = ['companies', 'reference_data'];
        
        return $this->cacheService->rememberWithTags($cacheKey, $tags, function () use ($name) {
            return $this->model->where('name', $name)->first();
        }, $this->cacheTtl);
    }
    public function invalidateCompanyCache(): void
    {
        $tags = ['companies', 'reference_data', 'statistics'];
        $this->cacheService->flushByTags($tags);
    }
    public function clearCompanyCache(): void
    {
        $this->invalidateCompanyCache();
    }
}