<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    use HasFactory;

    protected $fillable = [
        'svg_region','name','zone','parity','number',
        'price_adult','price_youth','capacity','available','color_hex','meta',
    ];

    protected $casts = [
        'available' => 'bool',
        'price_adult' => 'decimal:2',
        'price_youth' => 'decimal:2',
        'meta' => 'array',
    ];

    public function seats() { return $this->hasMany(Seat::class); }

    public function scopeAvailable($q) { return $q->where('available', true); }
    public function scopeByZone($q, string $zone) { return $q->where('zone', $zone); }

    public function getZoneLabelAttribute(): string
    {
        return match ($this->zone) {
            'tribuna_baja'   => 'Tribuna Baja',
            'tribuna_alta'   => 'Tribuna Alta',
            'preferente'     => 'Preferente',
            'fondo_norte'    => 'Fondo Norte',
            'fondo_sur'      => 'Fondo Sur',
            'palco'          => 'Palco de Honor',
            default          => 'Otros',
        };
    }

    public function getZoneColorAttribute(): string
    {
        return match ($this->zone) {
            'tribuna_baja', 'tribuna_alta' => '#CF2E2E',  // rojo Algeciras
            'preferente'                   => '#D4A24C',  // oro corona
            'fondo_norte'                  => '#0A0A0A',
            'fondo_sur'                    => '#1A1A1A',
            'palco'                        => '#7c3aed',  // morado palco
            default                        => '#9CA3AF',
        };
    }
}
