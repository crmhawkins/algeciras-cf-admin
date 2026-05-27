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

    protected $appends = ['tier', 'tier_label'];

    // Relaciones
    public function user()                   { return $this->belongsTo(User::class); }
    public function orders()                 { return $this->hasMany(Order::class); }
    public function tickets()                { return $this->hasMany(Ticket::class); }
    public function notificationPreferences(){ return $this->hasMany(NotificationPreference::class); }
    public function customerCoupons()        { return $this->hasMany(CustomerCoupon::class); }
    public function coupons()                { return $this->belongsToMany(Coupon::class, 'customer_coupons')->withPivot('status','redeemed_at','redeemed_via'); }
    public function matchAttendances()       { return $this->hasMany(MatchAttendance::class); }
    public function mvpVotes()               { return $this->hasMany(MvpVote::class); }

    // Scopes
    public function scopeSocios($q) { return $q->where('is_socio', true); }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Tier del socio para gamificación y filtrado de cupones.
     * Valores: 'aficionado' | 'abonado' | 'abonado_vip' | 'peñista'
     */
    public function getTierAttribute(): string
    {
        try {
            // VIP: ticket de abono cuya zona contenga 'palco' en el nombre/slug
            $hasPalcoAbono = $this->tickets()
                ->whereHas('product', fn ($q) => $q->where('type', 'abono'))
                ->whereHas('zone', fn ($q) => $q->where('name', 'like', '%palco%')->orWhere('slug', 'like', '%palco%'))
                ->exists();
            if ($hasPalcoAbono) return 'abonado_vip';

            // Si tiene cualquier abono activo
            $hasAbono = $this->tickets()
                ->whereHas('product', fn ($q) => $q->where('type', 'abono'))
                ->exists();
            if ($hasAbono) return 'abonado';
        } catch (\Throwable $e) {
            // Si la relación zone no existe o la BD aún no está consistente,
            // caemos a aficionado en lugar de romper toda la app.
        }

        return 'aficionado';
    }

    public function getTierLabelAttribute(): string
    {
        return match ($this->tier) {
            'abonado_vip' => 'Abonado VIP',
            'abonado'     => 'Abonado',
            'peñista'     => 'Peñista',
            default       => 'Aficionado',
        };
    }

    /** % de partidos en casa a los que ha asistido esta temporada */
    public function getAttendanceRateAttribute(): float
    {
        if (! $this->is_socio) return 0.0;

        $totalHome = \App\Models\FootballMatch::where('venue', 'home')
            ->whereIn('status', ['finished'])
            ->count();
        if ($totalHome === 0) return 0.0;

        $attended = $this->matchAttendances()->count();
        return round(($attended / $totalHome) * 100, 1);
    }
}
