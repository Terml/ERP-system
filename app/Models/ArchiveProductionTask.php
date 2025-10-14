<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveProductionTask extends Model
{
    protected $table = 'archived_production_tasks';
    protected $fillable = [
        'original_id',
        'original_order_id',
        'user_id',
        'quantity',
        'status',
        'archived_at',
    ];
    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
