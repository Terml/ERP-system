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
        return DB::transaction(function () use ($userData) {
            $user = $this->create([
                'login' => $userData['login'],
                'password' => Hash::make($userData['password']),
            ]);
            if (!empty($userData['role_ids'])) {
                $user->roles()->sync($userData['role_ids']);
            }
            return $user->load('roles');
        });
    }
    public function updateUserWithRoles(int $userId, array $userData): User
    {
        return DB::transaction(function () use ($userId, $userData) {
            $user = $this->findOrFail($userId);
            $updateData = [
                'login' => $userData['login'],
            ];
            if (!empty($userData['password'])) {
                $updateData['password'] = Hash::make($userData['password']);
            }
            $user->update($updateData);

            if (!empty($userData['role_ids'])) {
                $user->roles()->sync($userData['role_ids']);
            }
            return $user->load('roles');
        });
    }
}
