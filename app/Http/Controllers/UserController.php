<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RoleService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService,
        private RoleService $roleService,
    ) {
        // получение зависимостей конструктора, доступ к сервисам во всех методах ктрл
    }
    public function register(Request $request): JsonResponse
    {
        try {
            // валидация данных юзера
            $validated = $request->validate([
                'login' => 'required|string|unique:users,login',
                'password' => 'required|string|min:6',
                'roles' => 'required|array', // массив
                'roles.*' => 'string' // каждый элемент массива - строка
            ]);
            // создание юзера
            $user = $this->userService->createUserWithRoles(
                [
                    'login' => $validated['login'],
                    'password' => Hash::make($validated['password']),
                ],
                $validated['roles']
            );
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
}
