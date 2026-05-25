<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SponsorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'slug'            => $this->slug,
            'name'            => $this->name,
            'tier'            => $this->tier,
            'logo'            => $this->logo ? url($this->logo) : null,
            'logo_dark'       => $this->logo_dark ? url($this->logo_dark) : null,
            'url'             => $this->url,
            'invert_on_dark'  => (bool) $this->invert_on_dark,
            'description'     => $this->getTranslations('description'),
        ];
    }
}
