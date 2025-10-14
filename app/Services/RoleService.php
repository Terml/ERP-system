<?php

namespace App\Services;
use App\Models\Role;
use App\Services\CacheService;
use Illuminate\Database\Eloquent\Collection;

class RoleService extends BaseService
{
    protected CacheService $cacheService;
    protected int $cacheTtl = 3600;
    public function __construct(Role $role, CacheService $cacheService)
    {
        parent::__construct($role);
        $this->cacheService = $cacheService;
    }
    public function getAllRoles(): Collection
    {
        $cacheKey = 'roles:all';
        $tags = ['roles', 'reference_data'];
        return $this->cacheService->rememberWithTags($cacheKey, $tags, function () {
            return $this->model->orderBy('role')->get();
        }, $this->cacheTtl);
    }
    public function getRole(int $id): ?Role
    {
        $cacheKey = "role:{$id}";
        $tags = ['roles', 'reference_data'];
        return $this->cacheService->rememberWithTags($cacheKey, $tags, function () use ($id) {
            return $this->model->find($id);
        }, $this->cacheTtl);
    }
    public function getRolesForSelect(): array
    {
        $cacheKey = 'roles:select';
        $tags = ['roles', 'reference_data', 'select'];
        return $this->cacheService->rememberWithTags($cacheKey, $tags, function () {
            return $this->model->select('id', 'role', 'description')
                ->orderBy('role')
                ->get()
                ->map(function ($role) {
                    return [
                        'value' => $role->id,
                        'label' => $role->role,
                    ];
                })
                ->toArray();
        }, $this->cacheTtl);
    }
    public function getRoleByName(string $name): ?Role
    {
        $cacheKey = "role:name:{$name}";
        $tags = ['roles', 'reference_data'];
        return $this->cacheService->rememberWithTags($cacheKey, $tags, function () use ($name) {
            return $this->model->where('role', $name)->first();
        }, $this->cacheTtl);
    }
    public function getRoleStatistics(): array
    {
        $cacheKey = 'roles:statistics';
        $tags = ['roles', 'statistics'];
        return $this->cacheService->rememberWithTags($cacheKey, $tags, function () {
            $roles = $this->model->withCount('users')->get();
            return [
                'total_roles' => $roles->count(),
                'roles_with_users' => $roles->where('users_count', '>', 0)->count(),
                'roles_list' => $roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'role' => $role->role,
                        'description' => $role->description,
                        'users_count' => $role->users_count,
                    ];
                })->sortBy('role')->values()->toArray(),
            ];
        }, $this->cacheTtl);
    }
    public function invalidateRoleCache(): void
    {
        $tags = ['roles', 'reference_data', 'statistics'];
        $this->cacheService->flushByTags($tags);
    }
    public function clearRoleCache(): void
    {
        $this->invalidateRoleCache();
    }
}