<?php

namespace App\Filament\Resources\Attendances\Tables;

use App\Models\Attendance;
use App\Models\FootballMatch;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('match.opponent')
                    ->label('Partido')
                    ->formatStateUsing(function (?string $state, Attendance $record) {
                        $m = $record->match;
                        if (! $m) {
                            return '—';
                        }
                        $kick = $m->kickoff_at?->format('d/m/Y') ?? '—';
                        $jor  = $m->matchday ? "J{$m->matchday} " : '';
                        return trim("{$jor}vs {$m->opponent} ({$kick})");
                    })
                    ->searchable(query: fn (Builder $q, string $s) => $q
                        ->whereHas('match', fn ($m) => $m->where('opponent', 'like', "%{$s}%")))
                    ->wrap()
                    ->placeholder('—'),

                TextColumn::make('ticket.customer.full_name')
                    ->label('Cliente')
                    ->searchable(query: fn (Builder $q, string $s) => $q
                        ->whereHas('ticket.customer', fn ($c) => $c
                            ->where('first_name', 'like', "%{$s}%")
                            ->orWhere('last_name', 'like', "%{$s}%")
                            ->orWhere('email', 'like', "%{$s}%")))
                    ->placeholder('—')
                    ->wrap(),

                TextColumn::make('ticket.product.name')
                    ->label('Producto')
                    ->badge()
                    ->color(fn (Attendance $record) => match ($record->ticket?->product?->type) {
                        'abono'   => 'success',
                        'entrada' => 'warning',
                        default   => 'gray',
                    })
                    ->placeholder('—'),

                TextColumn::make('ticket.zone.name')
                    ->label('Zona')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),

                TextColumn::make('gate_id')
                    ->label('Puerta')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),

                TextColumn::make('scanned_at')
                    ->label('Escaneado')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                TextColumn::make('scannedBy.name')
                    ->label('Escaneado por')
                    ->placeholder('—'),
            ])
            ->defaultSort('scanned_at', 'desc')
            ->filters([
                SelectFilter::make('match_id')
                    ->label('Partido')
                    ->searchable()
                    ->options(fn () => FootballMatch::orderByDesc('kickoff_at')
                        ->limit(100)
                        ->get()
                        ->mapWithKeys(fn ($m) => [
                            $m->id => trim('J' . $m->matchday . ' vs ' . $m->opponent
                                . ' (' . ($m->kickoff_at?->format('d/m/Y') ?? '—') . ')'),
                        ])
                        ->toArray()),

                SelectFilter::make('ticket_type')
                    ->label('Tipo')
                    ->options([
                        'abono'   => 'Abono',
                        'entrada' => 'Entrada',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        if (! $value) {
                            return $query;
                        }
                        return $query->whereHas(
                            'ticket.product',
                            fn ($q) => $q->where('type', $value)
                        );
                    }),

                Filter::make('scanned_at_range')
                    ->label('Rango fecha escaneo')
                    ->schema([
                        DatePicker::make('scanned_from')->label('Desde'),
                        DatePicker::make('scanned_until')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['scanned_from']  ?? null, fn ($q, $d) => $q->whereDate('scanned_at', '>=', $d))
                            ->when($data['scanned_until'] ?? null, fn ($q, $d) => $q->whereDate('scanned_at', '<=', $d));
                    }),
            ])
            ->recordActions([
                ViewAction::make()->label('Ver'),
            ])
            ->toolbarActions([
                // read-only desde admin
            ]);
    }
}
