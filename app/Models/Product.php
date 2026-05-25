<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model
{
    use HasFactory, HasTranslations, HasSlug;

    public const TYPE_MERCH   = 'merch';
    public const TYPE_ABONO   = 'abono';
    public const TYPE_ENTRADA = 'entrada';

    public $translatable = ['name','description','short_description'];

    protected $fillable = [
        'sku','slug','type','category_id','name','description','short_description',
        'price','compare_at_price','vat_rate','image','gallery','active','featured','sort_order',
        'has_variants','ship_required','stock','weight_kg',
        'match_id','season_id','zone_id','capacity','sold',
        'sale_starts_at','sale_ends_at','socios_only',
    ];

    protected $casts = [
        'gallery' => 'array',
        'active' => 'bool',
        'featured' => 'bool',
        'has_variants' => 'bool',
        'ship_required' => 'bool',
        'socios_only' => 'bool',
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'weight_kg' => 'decimal:3',
        'sale_starts_at' => 'datetime',
        'sale_ends_at' => 'datetime',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(fn ($m) => ($m->sku.' '.$m->getTranslation('name','es')))
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string { return 'slug'; }

    public function category() { return $this->belongsTo(Category::class); }
    public function variants() { return $this->hasMany(ProductVariant::class); }
    public function match()    { return $this->belongsTo(FootballMatch::class, 'match_id'); }
    public function season()   { return $this->belongsTo(Season::class); }
    public function zone()     { return $this->belongsTo(Zone::class); }

    public function scopeActive($q)    { return $q->where('active', true); }
    public function scopeFeatured($q)  { return $q->where('featured', true); }
    public function scopeMerch($q)     { return $q->where('type', self::TYPE_MERCH); }
    public function scopeAbono($q)     { return $q->where('type', self::TYPE_ABONO); }
    public function scopeEntrada($q)   { return $q->where('type', self::TYPE_ENTRADA); }
    public function scopeOnSale($q)
    {
        return $q->where(function ($q) {
            $q->whereNull('sale_starts_at')->orWhere('sale_starts_at', '<=', now());
        })->where(function ($q) {
            $q->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>=', now());
        });
    }

    public function getIsAvailableAttribute(): bool
    {
        if (! $this->active) return false;
        if ($this->capacity !== null && $this->sold >= $this->capacity) return false;
        if ($this->stock !== null && $this->stock <= 0 && ! $this->has_variants) return false;
        return true;
    }

    public function getRemainingAttribute(): ?int
    {
        if ($this->capacity === null) return null;
        return max(0, $this->capacity - $this->sold);
    }
}
