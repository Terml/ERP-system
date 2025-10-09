<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'login',
        'password',
    ];
    protected $hidden = [
        'password',
    ];
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }
    public function addRole(string $roleName): void
    { // добавление роли
        $role = Role::where('role', $roleName)->first();
        if ($role) {
            $this->roles()->attach($role->id);
        }
    }
    public function removeRole(string $roleName): void
    { // удаление роли
        $role = Role::where('role', $roleName)->first();
        if ($role) {
            $this->roles()->detach($role->id);
        }
    }
    public function hasRole(string $roleName): bool
    { // проверка на роль
        return $this->roles()->where('role', $roleName)->exists();
    }
    public function getRoles(): Collection
    { // проверка на все роли пользователи
        return $this->roles;
    }
    public function syncRoles(array $roleNames): void
    { // синхронизация ролей, добавление ролей к пользователю при создании через массив ролей
        $roleIds = [];
        foreach ($roleNames as $roleName){
            $role = Role::where('role', $roleName)->first();
            if ($role){
                $roleIds[] = $role->id;
            }
        }
        $this->roles()->sync($roleIds);
    }
}
