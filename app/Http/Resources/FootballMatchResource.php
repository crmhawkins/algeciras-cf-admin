<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FootballMatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'matchday'   => $this->matchday,
            'competition' => $this->competition,
            'opponent'   => $this->opponent,
            'opponent_logo' => $this->opponent_logo ? url($this->opponent_logo) : null,
            'venue'      => $this->venue,
            'stadium'    => $this->stadium,
            'kickoff_at' => $this->kickoff_at?->toIso8601String(),
            'status'     => $this->status,
            'home_score' => $this->home_score,
            'away_score' => $this->away_score,
            'result'     => $this->result,
            'broadcast'  => $this->broadcast,
            'ticket_external_url' => $this->ticket_external_url,
            'season'     => $this->whenLoaded('season', fn () => [
                'id' => $this->season->id, 'name' => $this->season->name,
            ]),
        ];
    }
}
