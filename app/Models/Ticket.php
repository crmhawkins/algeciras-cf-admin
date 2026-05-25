<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id','customer_id','product_id','match_id','season_id','zone_id',
        'uuid','qr_token','qr_image_path','status','holder_name','holder_dni',
        'valid_from','valid_until','used_at','used_by_admin_id','used_gate',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'used_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $t) {
            if (! $t->uuid) {
                $t->uuid = (string) Str::uuid();
            }
            if (! $t->qr_token) {
                $t->qr_token = hash_hmac('sha256', $t->uuid, config('app.key'));
            }
        });
    }

    public function orderItem() { return $this->belongsTo(OrderItem::class); }
    public function customer()  { return $this->belongsTo(Customer::class); }
    public function product()   { return $this->belongsTo(Product::class); }
    public function match()     { return $this->belongsTo(FootballMatch::class, 'match_id'); }
    public function season()    { return $this->belongsTo(Season::class); }
    public function zone()      { return $this->belongsTo(Zone::class); }

    public function scopeIssued($q)    { return $q->where('status','issued'); }
    public function scopeUsed($q)      { return $q->where('status','used'); }

    public function getQrPayloadAttribute(): string
    {
        return $this->uuid.'.'.$this->qr_token;
    }
}
