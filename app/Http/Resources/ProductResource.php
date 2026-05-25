<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'sku'          => $this->sku,
            'slug'         => $this->slug,
            'type'         => $this->type,
            'category'     => $this->whenLoaded('category', fn () => [
                'id'   => $this->category->id,
                'slug' => $this->category->slug,
                'name' => $this->category->getTranslations('name'),
            ]),
            'name'         => $this->getTranslations('name'),
            'description'  => $this->getTranslations('description'),
            'short_description' => $this->getTranslations('short_description'),
            'price'        => (float) $this->price,
            'compare_at_price' => $this->compare_at_price ? (float) $this->compare_at_price : null,
            'vat_rate'     => $this->vat_rate,
            'image'        => $this->image ? url($this->image) : null,
            'gallery'      => collect($this->gallery ?? [])->map(fn ($g) => url($g))->all(),
            'featured'     => (bool) $this->featured,
            'available'    => $this->is_available,
            'remaining'    => $this->remaining,
            // Solo merch
            'has_variants' => (bool) $this->has_variants,
            'ship_required' => (bool) $this->ship_required,
            'variants'     => $this->whenLoaded('variants', fn () => $this->variants->map(fn ($v) => [
                'id' => $v->id, 'sku' => $v->sku, 'size' => $v->size, 'color' => $v->color,
                'price' => (float) $v->effective_price, 'stock' => $v->stock,
            ])),
            // Solo entrada/abono
            'match'  => $this->whenLoaded('match', fn () => $this->match ? [
                'id' => $this->match->id, 'opponent' => $this->match->opponent,
                'kickoff_at' => $this->match->kickoff_at?->toIso8601String(),
                'venue' => $this->match->venue,
            ] : null),
            'season' => $this->whenLoaded('season', fn () => $this->season ? [
                'id' => $this->season->id, 'name' => $this->season->name,
            ] : null),
            'zone'   => $this->whenLoaded('zone', fn () => $this->zone ? [
                'id' => $this->zone->id, 'slug' => $this->zone->slug, 'name' => $this->zone->name, 'color' => $this->zone->color,
            ] : null),
            'socios_only' => (bool) $this->socios_only,
            'sale_starts_at' => $this->sale_starts_at?->toIso8601String(),
            'sale_ends_at'   => $this->sale_ends_at?->toIso8601String(),
        ];
    }
}
