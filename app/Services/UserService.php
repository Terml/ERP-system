<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService extends BaseService
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }
    public function createUserWithRoles(array $userData): User
    {
        // валидация
        $validated = validator($userData, [
            'login' => 'required|string|unique:users,login',
            'password' => 'required|string|min:6',
            'roles' => 'required|array',
            'roles.*' => 'string'
        ])->validate();
        return DB::transaction(function () use ($validated) {
            $user = $this->create([
                'login' => $validated['login'],
                'password' => Hash::make($validated['password']),
            ]);
            if (!empty($validated['roles'])) {
                $user->syncRoles($validated['roles']);
            }
            return $user->load('roles');
        });
    }
}
