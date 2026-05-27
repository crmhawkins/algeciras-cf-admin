<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchAttendance extends Model
{
    protected $fillable = ['customer_id', 'match_id', 'ticket_id', 'checked_in_at', 'gate'];
    protected $casts = ['checked_in_at' => 'datetime'];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function match()    { return $this->belongsTo(\App\Models\FootballMatch::class, 'match_id'); }
    public function ticket()   { return $this->belongsTo(Ticket::class); }
}
