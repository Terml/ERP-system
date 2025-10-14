<?php

namespace App\Http\Controllers;

use App\Services\RoleService;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        private RoleService $roleService,
        private CacheService $cacheService
    ) {}
    public function index(): JsonResponse
    {
        try {
            $roles = $this->roleService->getAllRoles();
            return response()->json([
                'success' => true,
                'data' => $roles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения ролей',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function show(int $id): JsonResponse
    {
        try {
            $role = $this->roleService->getRole($id);
            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Роль не найдена'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => $role
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения роли',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function select(): JsonResponse
    {
        try {
            $roles = $this->roleService->getRolesForSelect();
            return response()->json([
                'success' => true,
                'data' => $roles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения ролей для выбора',
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
                    'message' => 'Не указано имя роли'
                ], 400);
            }
            $role = $this->roleService->getRoleByName($name);
            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Роль не найдена'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => $role
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка поиска роли',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->roleService->getRoleStatistics();
            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения статистики ролей',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function clearCache(): JsonResponse
    {
        try {
            $this->roleService->clearRoleCache();
            return response()->json([
                'success' => true,
                'message' => 'Кеш ролей очищен'
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
                'roles:all',
                'roles:select',
                'roles:statistics',
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
