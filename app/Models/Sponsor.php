<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Sponsor extends Model
{
    use HasFactory, HasTranslations, HasSlug;

    public $translatable = ['description'];

    protected $fillable = [
        'name','slug','tier','logo','logo_dark','url','invert_on_dark',
        'description','contract_start','contract_end','sort_order','active',
    ];

    protected $casts = [
        'active' => 'bool',
        'invert_on_dark' => 'bool',
        'contract_start' => 'date',
        'contract_end' => 'date',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string { return 'slug'; }

    public function scopeActive($q)         { return $q->where('active', true); }
    public function scopeTier($q, string $t) { return $q->where('tier', $t); }
}
