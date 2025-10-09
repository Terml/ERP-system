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
    public function index(): JsonResponse
    {
        try {
            $users = $this->userService->getAll();
            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
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
            $user = $this->userService->find($userId);
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
}
