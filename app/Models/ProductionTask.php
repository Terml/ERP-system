<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionTask extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'quantity',
        'status',
    ];
    public function order(): BelongsTo{
        return $this->belongsTo(Order::class);
    }
    public function user(): BelongsTo{
        return $this->belongsTo(User::class);
    }
    public function taskComponents(): HasMany{
        return $this->hasMany(TaskComponent::class);
    }
}
