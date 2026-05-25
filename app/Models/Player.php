<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Player extends Model
{
    use HasFactory, HasTranslations, HasSlug;

    public $translatable = ['bio'];

    protected $fillable = [
        'dorsal','slug','display_name','full_name','position','photo','photo_action',
        'birth_date','birth_place','nationality','height_cm','weight_kg','preferred_foot',
        'bio','instagram','x_handle','joined_at','contract_end','active','captain','sort_order',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'joined_at' => 'date',
        'contract_end' => 'date',
        'active' => 'bool',
        'captain' => 'bool',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('display_name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string { return 'slug'; }

    public function scopeActive($q)   { return $q->where('active', true); }
    public function scopeByPosition($q, string $p) { return $q->where('position', $p); }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date?->age;
    }
}
