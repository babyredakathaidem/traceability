<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'enterprise_id',
        'category_id',
        'name',
        'gtin',
        'description',
        'image_path',
        'unit',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function imageUrl(): ?string
    {
        return $this->image_path
            ? asset('storage/' . $this->image_path)
            : null;
    }
}