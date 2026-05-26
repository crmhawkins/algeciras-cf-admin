<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = ['sector_id','row','number','status'];

    public function sector() { return $this->belongsTo(Sector::class); }

    public function scopeFree($q)     { return $q->where('status', 'free'); }
    public function scopeReserved($q) { return $q->where('status', 'reserved'); }
    public function scopeSold($q)     { return $q->where('status', 'sold'); }
}
