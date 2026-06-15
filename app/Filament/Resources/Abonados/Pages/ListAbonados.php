<?php

namespace App\Filament\Resources\Abonados\Pages;

use App\Filament\Resources\Abonados\AbonadoResource;
use App\Models\Customer;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListAbonados extends ListRecords
{
    protected static string $resource = AbonadoResource::class;

    protected static ?string $title = 'Listado de abonados';

    protected function getHeaderActions(): array
    {
        return [
            // "Añadir nuevo abonado" — modal mínimo idéntico al del proveedor.
            Action::make('anadir_abonado')
                ->label('Añadir nuevo abonado')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->schema([
                    TextInput::make('first_name')->label('Nombre')->required()->maxLength(120),
                    TextInput::make('last_name')->label('Apellidos')->required()->maxLength(160),
                    Select::make('gender')->label('Género')->options([
                        'Hombre' => 'Hombre', 'Mujer' => 'Mujer', 'Otro' => 'Otro',
                    ])->native(false),
                    TextInput::make('dni')->label('DNI')->maxLength(20),
                    TextInput::make('socio_number')->label('Nº de abonado')->numeric(),
                    TextInput::make('num_antiguo')->label('Nº de abonado antiguo')->numeric(),
                ])
                ->action(function (array $data) {
                    Customer::create([
                        'first_name'   => $data['first_name'],
                        'last_name'    => $data['last_name'],
                        'gender'       => $data['gender'] ?? null,
                        'dni'          => $data['dni'] ?? null,
                        'socio_number' => $data['socio_number'] ?? null,
                        'is_socio'     => true,
                        'notes'        => ['num_abonado_antiguo' => $data['num_antiguo'] ?? null],
                    ]);
                    Notification::make()->title('Abonado creado')->success()->send();
                }),

            // "Opciones" masivas — réplica del menú superior del proveedor.
            ActionGroup::make([
                Action::make('exportar_csv')
                    ->label('Descargar listado (CSV)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (): StreamedResponse => response()->streamDownload(function () {
                        $out = fopen('php://output', 'w');
                        fprintf($out, "\xEF\xBB\xBF");
                        fputcsv($out, ['Nº abonado', 'DNI', 'Nombre', 'Apellidos', 'Email', 'Teléfono'], ';');
                        Customer::whereNotNull('socio_number')
                            ->orderBy('socio_number')
                            ->chunk(500, function ($chunk) use ($out) {
                                foreach ($chunk as $c) {
                                    fputcsv($out, [
                                        $c->socio_number, $c->dni, $c->first_name,
                                        $c->last_name, $c->email, $c->phone,
                                    ], ';');
                                }
                            });
                        fclose($out);
                    }, 'abonados-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8'])),

                Action::make('enviar_abonos_masivo')
                    ->label('Enviar abonos (email)')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Enviar abonos por email (masivo)')
                    ->modalDescription('DESACTIVADO por el kill switch. No se enviará ningún correo a ningún abonado.')
                    ->action(fn () => Notification::make()
                        ->title('Envío masivo bloqueado por kill switch')
                        ->body('No se ha enviado ningún correo.')
                        ->warning()
                        ->send()),
            ])
                ->label('Opciones')
                ->button()
                ->color('gray'),
        ];
    }
}
