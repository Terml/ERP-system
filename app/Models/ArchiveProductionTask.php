<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveProductionTask extends Model
{
    protected $table = 'archived_orders';
    protected $fillable = [
        'original_id',
        'company_id',
        'product_id',
        'quantity',
        'deadline',
        'status',
        'archived_at',
    ];
    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'archived_at' => 'datetime',
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
}
