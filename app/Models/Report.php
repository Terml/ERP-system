<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'data',
        'user_id',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function getDataSizeAttribute(): int
    {
        return strlen(json_encode($this->data));
    }
    public function getFormattedDataSizeAttribute(): string
    {
        $bytes = $this->data_size;
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
    public function isCompleted(): bool
    {
        return !empty($this->data);
    }
    public function getDescriptionAttribute(): string
    {
        return match ($this->type) {
            'by_company' => 'Отчет по компании',
            'by_product' => 'Отчет по продукту',
            'statistics' => 'Статистический отчет',
            default => 'Отчет',
        };
    }
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('data');
    }
}
