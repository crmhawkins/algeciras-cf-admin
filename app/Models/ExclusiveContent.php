<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExclusiveContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'cover_image',
        'category',
        'publish_at',
        'expires_at',
        'external_url',
        'discount_code',
        'is_published',
    ];

    protected $casts = [
        'publish_at'   => 'datetime',
        'expires_at'   => 'datetime',
        'is_published' => 'bool',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished($q)
    {
        return $q->where('is_published', true)
            ->where(function ($w) {
                $w->whereNull('publish_at')->orWhere('publish_at', '<=', now());
            })
            ->where(function ($w) {
                $w->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
    }

    public function getCoverUrlAttribute(): ?string
    {
        if (!$this->cover_image) {
            return null;
        }
        if (str_starts_with($this->cover_image, 'http')) {
            return $this->cover_image;
        }
        return asset($this->cover_image);
    }
}
