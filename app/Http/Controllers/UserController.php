<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}
    public function register(Request $request): JsonResponse
    {
        try {
            $user = $this->userService->createUserWithRoles($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Пользователь зарегистрирован',
                'data' => [
                    'id' => $user->id,
                    'login' => $user->login,
                    'roles' => $user->roles->pluck('role')
                ]
            ], 201);
        } catch (\Exception $e) { // обработка ошибок
            return response()->json([
                'success' => false,
                'message' => 'Ошибка регистрации',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function index(Request $request): JsonResponse
    {
        try {
            $query = \App\Models\User::with('roles');
            if ($request->has('role') && $request->input('role') !== '') {
                $role = $request->input('role');
                $query->whereHas('roles', function($q) use ($role) {
                    $q->where('role', $role);
                });
            }
            if ($request->has('search') && $request->input('search') !== '') {
                $search = $request->input('search');
                $query->where('login', 'like', "%{$search}%");
            }
            $sortBy = $request->input('sort_by', 'id');
            $sortOrder = $request->input('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);
            $perPage = $request->input('per_page', 15);
            $users = $query->paginate($perPage);
            return response()->json($users);
        } catch (\Exception $e) {
            \Log::error('Ошибка получения пользователей: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения пользователей',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function show(int $userId): JsonResponse
    {
        try {
            $user = \App\Models\User::with('roles')->find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не найден'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения пользователя',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'login' => 'required|string|max:255|unique:users',
                'password' => 'required|string|min:6',
                'role_ids' => 'required|array|min:1',
                'role_ids.*' => 'exists:roles,id'
            ]);
            $user = $this->userService->createUserWithRoles($validated);
            return response()->json([
                'success' => true,
                'message' => 'Пользователь создан успешно',
                'data' => $user->load('roles')
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации данных',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания пользователя',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function update(Request $request, int $userId): JsonResponse
    {
        try {
            $user = \App\Models\User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не найден'
                ], 404);
            }
            $validated = $request->validate([
                'login' => 'required|string|max:255|unique:users,login,' . $userId,
                'password' => 'sometimes|string|min:6',
                'role_ids' => 'required|array|min:1',
                'role_ids.*' => 'exists:roles,id'
            ]);
            $user = $this->userService->updateUserWithRoles($userId, $validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Пользователь обновлен успешно',
                'data' => $user->load('roles')
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации данных',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка обновления пользователя',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function destroy(int $userId): JsonResponse
    {
        try {
            $user = \App\Models\User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не найден'
                ], 404);
            }
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Нельзя удалить самого себя'
                ], 400);
            }
            $user->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Пользователь удален успешно'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка удаления пользователя',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
