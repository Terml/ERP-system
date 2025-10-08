<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'type',
        'unit',
    ];
    public function orders(): HasMany{
        return $this->hasMany(Order::class);
    }
    public function taskComponents(): HasMany{
        return $this->hasMany(TaskComponent::class);
    }
}
