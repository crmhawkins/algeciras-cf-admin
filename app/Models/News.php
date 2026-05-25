<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class News extends Model
{
    use HasFactory, HasTranslations, HasSlug;

    protected $table = 'news';

    public $translatable = ['title','excerpt','body'];

    protected $fillable = [
        'slug','title','excerpt','body','cover_image','category','author_id',
        'published_at','featured','views',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'featured' => 'bool',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(fn ($m) => $m->getTranslation('title','es'))
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string { return 'slug'; }

    public function author() { return $this->belongsTo(User::class, 'author_id'); }

    public function scopePublished($q) { return $q->whereNotNull('published_at')->where('published_at','<=', now()); }
    public function scopeFeatured($q)  { return $q->where('featured', true); }
}
