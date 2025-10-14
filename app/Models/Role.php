<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class Role extends Model
{
    protected $fillable = [
        'role',
        'description',
    ];
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }
    public function getUsers(): Collection
    { // пользователи с определенной ролью
        return $this->users()->get();
    }
}
