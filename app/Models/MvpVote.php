<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MvpVote extends Model
{
    use HasFactory;

    protected $table = 'mvp_votes';

    protected $fillable = [
        'match_id',
        'player_id',
        'customer_id',
        'voter_ip',
    ];

    public function match()
    {
        return $this->belongsTo(FootballMatch::class, 'match_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeForMatch($query, $matchId)
    {
        return $query->where('match_id', $matchId);
    }
}
