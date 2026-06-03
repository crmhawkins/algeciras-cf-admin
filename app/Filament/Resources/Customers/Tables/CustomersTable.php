<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerCoupon;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Nombre')
                    ->searchable(query: fn (Builder $q, string $s) => $q
                        ->where('first_name', 'like', "%{$s}%")
                        ->orWhere('last_name', 'like', "%{$s}%"))
                    ->sortable(query: fn (Builder $q, string $dir) => $q
                        ->orderBy('first_name', $dir)
                        ->orderBy('last_name', $dir)),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),

                TextColumn::make('dni')
                    ->label('DNI')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->placeholder('—')
                    ->copyable(),

                TextColumn::make('tier_label')
                    ->label('Tier')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Socio'      => 'success',
                        'Premium'    => 'warning',
                        'Aficionado' => 'gray',
                        default      => 'gray',
                    }),

                IconColumn::make('is_socio')
                    ->label('Socio')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('city')
                    ->label('Ciudad')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('province')
                    ->label('Provincia')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_socio')->label('Es socio'),
                TernaryFilter::make('newsletter_optin')->label('Newsletter'),
                TernaryFilter::make('whatsapp_optin')->label('WhatsApp'),

                SelectFilter::make('city')
                    ->label('Ciudad')
                    ->options(fn () => Customer::query()
                        ->whereNotNull('city')
                        ->distinct()
                        ->orderBy('city')
                        ->pluck('city', 'city')
                        ->toArray())
                    ->searchable(),

                Filter::make('con_abono')
                    ->label('Tiene abono activo')
                    ->toggle()
                    ->query(fn (Builder $q) => $q->whereHas('tickets', fn ($t) => $t
                        ->whereHas('product', fn ($p) => $p->where('type', 'abono'))
                        ->whereIn('status', ['issued', 'valid']))),

                Filter::make('fecha_registro')
                    ->label('Fecha registro')
                    ->schema([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(function (Builder $q, array $data) {
                        if (!empty($data['desde'])) {
                            $q->whereDate('created_at', '>=', $data['desde']);
                        }
                        if (!empty($data['hasta'])) {
                            $q->whereDate('created_at', '<=', $data['hasta']);
                        }
                    }),
            ])
            ->recordActions([
                ViewAction::make()->label('Ver'),
                EditAction::make()->label('Editar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('export_csv')
                        ->label('Exportar a CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function (Collection $records): StreamedResponse {
                            $filename = 'clientes-' . now()->format('Ymd-His') . '.csv';

                            return response()->streamDownload(function () use ($records) {
                                $out = fopen('php://output', 'w');
                                // BOM UTF-8 para Excel
                                fprintf($out, "\xEF\xBB\xBF");
                                fputcsv($out, [
                                    'ID', 'Nombre', 'Apellidos', 'Email', 'DNI', 'Teléfono',
                                    'Dirección', 'CP', 'Ciudad', 'Provincia',
                                    'Socio', 'Newsletter', 'WhatsApp', 'Fecha registro',
                                ], ';');
                                foreach ($records as $c) {
                                    fputcsv($out, [
                                        $c->id, $c->first_name, $c->last_name, $c->email,
                                        $c->dni, $c->phone,
                                        $c->address, $c->postal_code, $c->city, $c->province,
                                        $c->is_socio ? 'Sí' : 'No',
                                        $c->newsletter_optin ? 'Sí' : 'No',
                                        $c->whatsapp_optin ? 'Sí' : 'No',
                                        optional($c->created_at)->format('d/m/Y H:i'),
                                    ], ';');
                                }
                                fclose($out);
                            }, $filename, [
                                'Content-Type' => 'text/csv; charset=UTF-8',
                            ]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('asignar_cupon')
                        ->label('Asignar cupón')
                        ->icon('heroicon-o-ticket')
                        ->color('success')
                        ->schema([
                            Select::make('coupon_id')
                                ->label('Cupón')
                                ->required()
                                ->searchable()
                                ->options(fn () => Coupon::active()->orderBy('code')->get()
                                    ->mapWithKeys(fn ($c) => [$c->id => $c->code . ' — ' . $c->title])
                                    ->toArray()),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            $couponId = (int) ($data['coupon_id'] ?? 0);
                            if (! $couponId) {
                                return;
                            }
                            $asignados = 0;
                            foreach ($records as $customer) {
                                $cc = CustomerCoupon::firstOrCreate(
                                    [
                                        'customer_id' => $customer->id,
                                        'coupon_id'   => $couponId,
                                    ],
                                    [
                                        'status' => 'available',
                                    ]
                                );
                                if ($cc->wasRecentlyCreated) {
                                    $asignados++;
                                }
                            }
                            Notification::make()
                                ->title("{$asignados} cupones asignados a {$records->count()} clientes")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
