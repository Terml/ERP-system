<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserService extends BaseService
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }
    public function createUserWithRoles(array $userData, array $roleNames = []): User
    { // Создание пользователя с ролями
        return DB::transaction(function () use ($userData, $roleNames) {
            $user = $this->create($userData);
            if (!empty($roleNames)) {
                $user->syncRoles($roleNames);
            }
            return $user->load('roles');
        });
    }
}
