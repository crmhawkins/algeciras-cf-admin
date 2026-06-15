<?php

namespace App\Filament\Resources\Eventos\Schemas;

use App\Models\Season;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Formulario "Crear / Editar evento" — réplica del proveedor.
 */
class EventoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos del evento')
                ->columns(2)
                ->schema([
                    TextInput::make('opponent')->label('Rival')->required()->maxLength(120),
                    TextInput::make('matchday')->label('Jornada')->numeric()->minValue(1),
                    Select::make('competition')->label('Competición')
                        ->options([
                            'Primera RFEF'   => 'Primera RFEF',
                            'Copa del Rey'   => 'Copa del Rey',
                            'Amistoso'       => 'Amistoso',
                            'Play Off'       => 'Play Off',
                        ])
                        ->default('Primera RFEF')
                        ->native(false)
                        ->required(),
                    Select::make('venue')->label('Condición')
                        ->options(['home' => 'Local', 'away' => 'Visitante'])
                        ->default('home')
                        ->native(false)
                        ->required(),
                    Select::make('season_id')->label('Temporada')
                        ->options(fn () => Season::orderByDesc('id')->pluck('name', 'id')->toArray())
                        ->default(fn () => Season::current()?->id ?? Season::max('id'))
                        ->native(false)
                        ->required(),
                    TextInput::make('stadium')->label('Estadio / ubicación')->default('Nuevo Mirador')->required(),
                    DateTimePicker::make('kickoff_at')->label('Fecha y hora')->required()->seconds(false),
                    Select::make('status')->label('Estado')
                        ->options([
                            'scheduled' => 'Programado',
                            'live'      => 'En juego',
                            'finished'  => 'Finalizado',
                            'postponed' => 'Aplazado',
                        ])
                        ->default('scheduled')
                        ->native(false)
                        ->required(),
                ]),

            Section::make('Resultado / extras')
                ->columns(2)
                ->collapsible()
                ->collapsed()
                ->schema([
                    TextInput::make('home_score')->label('Goles Algeciras')->numeric()->minValue(0),
                    TextInput::make('away_score')->label('Goles rival')->numeric()->minValue(0),
                    TextInput::make('broadcast')->label('Retransmisión'),
                    TextInput::make('opponent_logo')->label('Escudo rival (URL)'),
                    Textarea::make('notes')->label('Notas')->columnSpanFull(),
                ]),
        ]);
    }
}
