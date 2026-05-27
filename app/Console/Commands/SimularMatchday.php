<?php

namespace App\Console\Commands;

use App\Models\FootballMatch;
use App\Models\Season;
use Illuminate\Console\Command;

/**
 * Crea/actualiza un partido EN CASA para hoy y poder probar la vista
 * Matchday del área personal inmediatamente.
 *
 *   php artisan simular:matchday
 *   php artisan simular:matchday "Cádiz B"
 */
class SimularMatchday extends Command
{
    protected $signature = 'simular:matchday {opponent? : Nombre del rival (default: "Cádiz B")}';
    protected $description = 'Crea o actualiza un Match en casa para HOY +4h y poder probar el modo Matchday.';

    public function handle(): int
    {
        $opponent = $this->argument('opponent') ?: 'Cádiz B';

        $season = Season::current() ?? Season::orderByDesc('id')->first();
        if (! $season) {
            $this->error('No hay ninguna Season en BD. Crea una primero (seed/admin).');
            return self::FAILURE;
        }

        $kickoff = now()->copy()->addHours(4);

        // Si ya hay un partido hoy en casa, lo reutilizamos para no
        // ensuciar con duplicados al ejecutar el comando varias veces.
        $match = FootballMatch::whereDate('kickoff_at', today())
            ->where('venue', 'home')
            ->first();

        $attrs = [
            'season_id'   => $season->id,
            'matchday'    => 99,
            'competition' => 'Primera RFEF',
            'opponent'    => $opponent,
            'venue'       => 'home',
            'stadium'     => 'Nuevo Mirador',
            'kickoff_at'  => $kickoff,
            'status'      => 'scheduled',
        ];

        if ($match) {
            $match->update($attrs);
            $action = 'actualizado';
        } else {
            $match = FootballMatch::create($attrs);
            $action = 'creado';
        }

        $this->info("Match #{$match->id} {$action}:");
        $this->line("  Rival      : {$match->opponent}");
        $this->line("  Kickoff    : {$match->kickoff_at?->format('Y-m-d H:i')} ({$kickoff->diffForHumans()})");
        $this->line("  Venue      : {$match->venue}");
        $this->line("  Status     : {$match->status}");
        $this->line("  Season     : {$season->name} (id={$season->id})");
        $this->newLine();
        $this->line('Entra a /area-personal logueado para ver el modo Matchday.');

        return self::SUCCESS;
    }
}
