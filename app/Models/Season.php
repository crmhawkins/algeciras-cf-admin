<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    use HasFactory;

    protected $fillable = ['name','start_at','end_at','current'];

    protected $casts = [
        'start_at' => 'date',
        'end_at' => 'date',
        'current' => 'bool',
    ];

    public function matches() { return $this->hasMany(FootballMatch::class); }
    public function abonos()  { return $this->hasMany(Product::class)->where('type','abono'); }

    public static function current(): ?self
    {
        return static::where('current', true)->first();
    }
}
