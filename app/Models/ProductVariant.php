<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = ['product_id','sku','size','color','price_override','stock','active','sort_order'];

    protected $casts = [
        'active' => 'bool',
        'price_override' => 'decimal:2',
    ];

    public function product() { return $this->belongsTo(Product::class); }

    public function getEffectivePriceAttribute(): string
    {
        return $this->price_override ?? $this->product->price;
    }
}
