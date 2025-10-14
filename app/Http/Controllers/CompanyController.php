<?php

namespace App\Http\Controllers;

use App\Services\CompanyService;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function __construct(
        private CompanyService $companyService,
        private CacheService $cacheService
    ) {}
    public function index(): JsonResponse
    {
        try {
            $companies = $this->companyService->getAllCompanies();
            return response()->json([
                'success' => true,
                'data' => $companies
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения компаний',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function show(int $id): JsonResponse
    {
        try {
            $company = $this->companyService->getCompany($id);
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Компания не найдена'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => $company
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения компании',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function select(): JsonResponse
    {
        try {
            $companies = $this->companyService->getCompaniesForSelect();
            return response()->json([
                'success' => true,
                'data' => $companies
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения компаний для выбора',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->input('q');
            if (!$query) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не указан поисковый запрос'
                ], 400);
            }
            $companies = $this->companyService->searchCompanies($query);
            return response()->json([
                'success' => true,
                'data' => $companies,
                'query' => $query
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка поиска компаний',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function withOrders(): JsonResponse
    {
        try {
            $companies = $this->companyService->getCompaniesWithOrderCount();
            return response()->json([
                'success' => true,
                'data' => $companies
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения компаний с заказами',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getByName(Request $request): JsonResponse
    {
        try {
            $name = $request->input('name');
            if (!$name) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не указано название компании'
                ], 400);
            }
            $company = $this->companyService->getCompanyByName($name);
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Компания не найдена'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => $company
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка поиска компании',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->companyService->getCompanyStatistics();
            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения статистики компаний',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function clearCache(): JsonResponse
    {
        try {
            $this->companyService->clearCompanyCache();
            return response()->json([
                'success' => true,
                'message' => 'Кеш компаний очищен'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка очистки кеша',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function cacheInfo(): JsonResponse
    {
        try {
            $cacheKeys = [
                'companies:all',
                'companies:select',
                'companies:with_orders',
                'companies:statistics',
            ];
            $cacheInfo = [];
            foreach ($cacheKeys as $key) {
                $cacheInfo[$key] = [
                    'exists' => $this->cacheService->has($key),
                    'ttl' => $this->cacheService->ttl($key),
                ];
            }
            return response()->json([
                'success' => true,
                'data' => $cacheInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения информации о кеше',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
