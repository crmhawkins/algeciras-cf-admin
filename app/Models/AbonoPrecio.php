<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Tipo de abono con su precio — réplica de "Configurar precios de los abonos"
 * del proveedor. zona × modalidad (nueva|renovacion) × adulto/infantil.
 */
class AbonoPrecio extends Model
{
    use HasFactory;

    protected $table = 'abono_precios';

    protected $fillable = [
        'provider_id', 'descripcion', 'zona', 'modalidad', 'es_infantil',
        'precio', 'edad_min', 'edad_max', 'activo', 'renovacion',
        'pago_plazos', 'stock', 'orden',
    ];

    protected $casts = [
        'es_infantil' => 'bool',
        'activo'      => 'bool',
        'renovacion'  => 'bool',
        'pago_plazos' => 'bool',
        'precio'      => 'decimal:2',
        'edad_min'    => 'integer',
        'edad_max'    => 'integer',
        'stock'       => 'integer',
        'orden'       => 'integer',
    ];

    public const ZONAS = [
        'tribuna_alta' => 'Tribuna Alta',
        'preferente'   => 'Preferencia',
        'fondo_sur'    => 'Fondo Sur',
        'fondo_norte'  => 'Fondo Norte',
        'palco'        => 'Palco de Honor',
    ];

    public const MODALIDADES = [
        'nueva'      => 'Alta nueva',
        'renovacion' => 'Renovación',
    ];

    public function getZonaLabelAttribute(): string
    {
        return self::ZONAS[$this->zona] ?? ucfirst((string) $this->zona);
    }

    public function getModalidadLabelAttribute(): string
    {
        return self::MODALIDADES[$this->modalidad] ?? ucfirst((string) $this->modalidad);
    }

    public function getRangoEdadAttribute(): ?string
    {
        if ($this->edad_min === null && $this->edad_max === null) {
            return null;
        }

        return trim(($this->edad_min ?? '') . '-' . ($this->edad_max ?? ''));
    }

    public function scopeActivos($q)
    {
        return $q->where('activo', true);
    }

    public function scopeZona($q, string $zona)
    {
        return $q->where('zona', $zona);
    }
}
