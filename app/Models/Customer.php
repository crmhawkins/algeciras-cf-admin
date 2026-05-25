<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','first_name','last_name','email','phone','dni','birth_date',
        'address','city','province','postal_code','country',
        'is_socio','socio_number','socio_since','language',
        'newsletter_optin','whatsapp_optin','notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'socio_since' => 'date',
        'is_socio' => 'bool',
        'newsletter_optin' => 'bool',
        'whatsapp_optin' => 'bool',
        'notes' => 'array',
    ];

    public function user()   { return $this->belongsTo(User::class); }
    public function orders() { return $this->hasMany(Order::class); }
    public function tickets() { return $this->hasMany(Ticket::class); }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function scopeSocios($q) { return $q->where('is_socio', true); }
}
