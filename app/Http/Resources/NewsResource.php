<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'slug'         => $this->slug,
            'title'        => $this->getTranslations('title'),
            'excerpt'      => $this->getTranslations('excerpt'),
            'body'         => $this->when($request->routeIs('*news.show'), fn () => $this->getTranslations('body')),
            'cover_image'  => $this->cover_image ? url($this->cover_image) : null,
            'category'     => $this->category,
            'featured'     => (bool) $this->featured,
            'views'        => $this->views,
            'published_at' => $this->published_at?->toIso8601String(),
            'author'       => $this->whenLoaded('author', fn () => $this->author ? [
                'id' => $this->author->id, 'name' => $this->author->name,
            ] : null),
        ];
    }
}
