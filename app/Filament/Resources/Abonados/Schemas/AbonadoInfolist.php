<?php

namespace App\Filament\Resources\Abonados\Schemas;

use App\Models\Customer;
use App\Models\Season;
use App\Models\Ticket;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Ficha "Ver abonado" — réplica del detalle del proveedor: Datos personales +
 * Domicilio + Datos del abono (asiento, tipo, precio, QR del abono de la
 * temporada actual).
 */
class AbonadoInfolist
{
    /** Memo del ticket de abono por customer (una vista = un request). */
    protected static array $memo = [];

    protected static function abono(Customer $r): ?Ticket
    {
        if (array_key_exists($r->id, static::$memo)) {
            return static::$memo[$r->id];
        }
        $sid = Season::current()?->id ?? Season::max('id');

        $ticket = $r->tickets()
            ->where('season_id', $sid)
            ->with(['seat.sector', 'season'])
            ->latest('id')->first()
            ?? $r->tickets()->with(['seat.sector', 'season'])->latest('id')->first();

        return static::$memo[$r->id] = $ticket;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos personales')
                ->columns(3)
                ->schema([
                    TextEntry::make('full_name')->label('Nombre completo'),
                    TextEntry::make('socio_number')->label('Nº de abonado'),
                    TextEntry::make('num_antiguo')->label('Nº abonado antiguo')
                        ->getStateUsing(function (Customer $r) {
                            $v = is_array($r->notes) ? ($r->notes['num_abonado_antiguo'] ?? null) : null;
                            return ($v === null || $v === '') ? null : (string) (int) $v;
                        })
                        ->placeholder('—'),
                    TextEntry::make('gender')->label('Género')->placeholder('—'),
                    TextEntry::make('phone')->label('Teléfono')->placeholder('—')->copyable(),
                    TextEntry::make('email')->label('Email')->copyable(),
                    TextEntry::make('birth_date')->label('Fecha de nacimiento')->date('d/m/Y')->placeholder('—'),
                    TextEntry::make('dni')->label('DNI')->placeholder('—'),
                    TextEntry::make('pena')->label('Peña / empresa')
                        ->getStateUsing(fn (Customer $r) => is_array($r->notes) ? ($r->notes['penista'] ?? ($r->notes['pena'] ?? null)) : null)
                        ->placeholder('—'),
                ]),

            Section::make('Domicilio')
                ->columns(3)
                ->collapsible()
                ->schema([
                    TextEntry::make('address')->label('Dirección')->placeholder('—'),
                    TextEntry::make('postal_code')->label('CP')->placeholder('—'),
                    TextEntry::make('city')->label('Localidad')->placeholder('—'),
                    TextEntry::make('province')->label('Provincia')->placeholder('—'),
                    TextEntry::make('country')->label('País')->placeholder('—'),
                ]),

            Section::make('Datos del abono')
                ->columns(3)
                ->schema([
                    TextEntry::make('asiento')->label('Asiento')
                        ->getStateUsing(function (Customer $r) {
                            $seat = static::abono($r)?->seat;
                            if (! $seat) {
                                return null;
                            }
                            $sector = $seat->sector?->name ?? '';
                            return trim($sector . '  ·  Fila ' . $seat->row . '  ·  Butaca ' . $seat->number);
                        })
                        ->placeholder('Sin asiento asignado'),
                    TextEntry::make('tipo_abono')->label('Tipo de abono')
                        ->getStateUsing(fn (Customer $r) => is_array($r->notes) ? ($r->notes['tipo_precio'] ?? null) : null)
                        ->placeholder('—'),
                    TextEntry::make('precio')->label('Precio')
                        ->getStateUsing(function (Customer $r) {
                            $t = static::abono($r);
                            return ($t && $t->price_paid !== null)
                                ? number_format((float) $t->price_paid, 2, ',', '.') . ' €'
                                : null;
                        })
                        ->placeholder('—'),
                    TextEntry::make('temporada')->label('Temporada')
                        ->getStateUsing(fn (Customer $r) => static::abono($r)?->season?->name)
                        ->placeholder('—'),
                    TextEntry::make('socio_since')->label('Fecha de antigüedad')->date('d/m/Y')->placeholder('—'),
                    TextEntry::make('qr')->label('QR del abono')
                        ->getStateUsing(fn (Customer $r) => static::abono($r)?->qrContent())
                        ->placeholder('—')
                        ->copyable(),
                ]),
        ]);
    }
}
