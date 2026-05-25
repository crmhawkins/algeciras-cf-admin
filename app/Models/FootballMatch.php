<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FootballMatch extends Model
{
    use HasFactory;

    /**
     * `match` es palabra reservada en PHP. La tabla sigue siendo `matches` pero
     * el modelo se llama FootballMatch para evitar conflictos.
     */
    protected $table = 'matches';

    protected $fillable = [
        'season_id','matchday','competition','opponent','opponent_logo','venue',
        'stadium','kickoff_at','status','home_score','away_score',
        'broadcast','ticket_external_url','notes',
    ];

    protected $casts = [
        'kickoff_at' => 'datetime',
        'notes' => 'array',
    ];

    public function season()   { return $this->belongsTo(Season::class); }
    public function products() { return $this->hasMany(Product::class, 'match_id'); }
    public function tickets()  { return $this->hasMany(Ticket::class, 'match_id'); }

    public function scopeUpcoming($q) { return $q->where('status','scheduled')->where('kickoff_at','>=', now())->orderBy('kickoff_at'); }
    public function scopeFinished($q) { return $q->where('status','finished')->orderByDesc('kickoff_at'); }
    public function scopeHome($q)     { return $q->where('venue','home'); }

    public function getResultAttribute(): ?string
    {
        if ($this->status !== 'finished') return null;
        return ($this->venue === 'home')
            ? "Algeciras CF {$this->home_score} - {$this->away_score} {$this->opponent}"
            : "{$this->opponent} {$this->away_score} - {$this->home_score} Algeciras CF";
    }
}
