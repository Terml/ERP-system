<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'company_id',
        'product_id',
        'quantity',
        'deadline',
        'status',
    ];
    protected function casts(): array
    {
        return [
            'deadline' => 'date',
        ];
    }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function productionTasks(): HasMany
    {
        return $this->hasMany(ProductionTask::class);
    }
}
