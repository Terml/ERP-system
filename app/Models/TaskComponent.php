<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskComponent extends Model
{
    protected $fillable = [
        'production_task_id',
        'product_id',
        'material_quantity',
    ];
    public function productionTask(): BelongsTo{
        return $this->belongsTo(ProductionTask::class);
    }
    public function product(): BelongsTo{
        return $this->belongsTo(Product::class);
    }
}
