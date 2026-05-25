<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'slug'       => $this->slug,
            'dorsal'     => $this->dorsal,
            'name'       => $this->display_name,
            'full_name'  => $this->full_name,
            'position'   => $this->position,
            'photo'      => $this->photo ? url($this->photo) : null,
            'photo_action' => $this->photo_action ? url($this->photo_action) : null,
            'nationality' => $this->nationality,
            'age'        => $this->age,
            'height_cm'  => $this->height_cm,
            'weight_kg'  => $this->weight_kg,
            'preferred_foot' => $this->preferred_foot,
            'captain'    => (bool) $this->captain,
            'instagram'  => $this->instagram,
            'x_handle'   => $this->x_handle,
            'bio'        => $this->getTranslations('bio'),
            'joined_at'  => $this->joined_at?->toDateString(),
        ];
    }
}
