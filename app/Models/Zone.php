<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    protected $fillable = ['slug','name','color','capacity_total','sort_order'];

    public function products() { return $this->hasMany(Product::class); }
    public function tickets()  { return $this->hasMany(Ticket::class); }
}
