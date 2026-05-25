<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClubStaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'role'       => $this->role,
            'department' => $this->department,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'photo'      => $this->photo ? url($this->photo) : null,
        ];
    }
}
