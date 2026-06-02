<?php

namespace App\Filament\Resources\Tickets\Tables;

use App\Models\FootballMatch;
use App\Models\Season;
use App\Models\Ticket;
use App\Services\QrService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('customer.full_name')
                    ->label('Cliente')
                    ->searchable(query: fn (Builder $q, string $s) => $q
                        ->whereHas('customer', fn ($c) => $c
                            ->where('first_name', 'like', "%{$s}%")
                            ->orWhere('last_name', 'like', "%{$s}%")
                            ->orWhere('email', 'like', "%{$s}%")))
                    ->placeholder('—')
                    ->wrap(),

                TextColumn::make('product.name')
                    ->label('Producto')
                    ->searchable()
                    ->wrap()
                    ->placeholder('—'),

                TextColumn::make('zone.name')
                    ->label('Zona')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'issued' => 'success',
                        'valid'  => 'success',
                        'used'   => 'gray',
                        'voided' => 'danger',
                        default  => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('match.opponent')
                    ->label('Partido')
                    ->formatStateUsing(function (?string $state, Ticket $record) {
                        if (! $record->match) {
                            return '—';
                        }
                        $m = $record->match;
                        $kick = $m->kickoff_at?->format('d/m/Y') ?? '';
                        return trim("vs {$m->opponent} {$kick}");
                    })
                    ->placeholder('—'),

                ImageColumn::make('qr_image_path')
                    ->label('QR')
                    ->size(40)
                    ->getStateUsing(function (Ticket $record): ?string {
                        if (! $record->qr_image_path) {
                            return null;
                        }
                        // QrService guarda 'storage/tickets/xxx.png' — asset() devuelve URL pública.
                        return asset($record->qr_image_path);
                    }),

                TextColumn::make('holder_dni')
                    ->label('DNI titular')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Emitido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'issued' => 'Emitido',
                        'valid'  => 'Válido',
                        'used'   => 'Usado',
                        'voided' => 'Anulado',
                    ]),
                SelectFilter::make('season_id')
                    ->label('Temporada')
                    ->options(fn () => Season::orderByDesc('id')->pluck('name', 'id')->toArray()),
                SelectFilter::make('match_id')
                    ->label('Partido')
                    ->searchable()
                    ->options(fn () => FootballMatch::orderByDesc('kickoff_at')
                        ->limit(100)
                        ->get()
                        ->mapWithKeys(fn ($m) => [
                            $m->id => trim("J{$m->matchday} vs {$m->opponent} (" . ($m->kickoff_at?->format('d/m/Y') ?? '—') . ')'),
                        ])
                        ->toArray()),
            ])
            ->recordActions([
                ViewAction::make()->label('Ver'),
                EditAction::make()->label('Editar'),
                Action::make('regenerate_qr')
                    ->label('Regenerar QR')
                    ->icon('heroicon-o-qr-code')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Regenerar QR del ticket')
                    ->modalDescription('Se generará una nueva imagen QR. El payload (uuid + token) NO cambia.')
                    ->action(function (Ticket $record): void {
                        try {
                            app(QrService::class)->generate($record);
                            Notification::make()
                                ->title("QR regenerado para ticket #{$record->id}")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Error regenerando QR')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
