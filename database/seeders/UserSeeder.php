<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'login' => 'admin',
                'password' => Hash::make('admin123'),
                'roles' => ['admin']
            ],
            [
                'login' => 'manager',
                'password' => Hash::make('manager123'),
                'roles' => ['manager']
            ],
            [
                'login' => 'dispatcher',
                'password' => Hash::make('dispatcher123'),
                'roles' => ['dispatcher']
            ],
            [
                'login' => 'master',
                'password' => Hash::make('master123'),
                'roles' => ['master']
            ],
            [
                'login' => 'otk',
                'password' => Hash::make('otk123'),
                'roles' => ['otk']
            ],
            [
                'login' => 'superuser',
                'password' => Hash::make('super123'),
                'roles' => ['admin', 'manager', 'dispatcher', 'master', 'otk']
            ]
        ];
        foreach ($users as $userData) {
            $roles = $userData['roles'];
            unset($userData['roles']); // убрать роль, тк нет строки в базе
            
            $user = User::create($userData);
            $user->syncRoles($roles); // назначить роль юзеру
        }
    }
}
