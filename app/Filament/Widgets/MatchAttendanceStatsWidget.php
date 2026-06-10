<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\FootballMatch;
use App\Models\Sector;
use App\Models\Zone;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class MatchAttendanceStatsWidget extends Widget
{
    protected string $view = 'filament.widgets.match-attendance-stats';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -10;

    // El polling lo hace la propia blade vista con wire:poll.{intervalo};
    // no uso el trait CanPoll porque en Filament 5 colisiona con la
    // propiedad estática.

    /**
     * Intervalo de polling para wire:poll en la blade. null = sin polling.
     * Usado por match-attendance-stats.blade.php para construir el atributo
     * wire:poll.30s en modo live, sin polling en upcoming/none.
     */
    public function getPollingInterval(): ?string
    {
        $now = now();
        $hayLive = FootballMatch::query()
            ->whereBetween('kickoff_at', [$now->copy()->subHours(4), $now->copy()->addHours(4)])
            ->exists();

        return $hayLive ? '30s' : null;
    }

    /**
     * Datos para la vista.
     *
     * Tres posibles estados:
     *  - 'live'    → hay partido en ventana ±4h
     *  - 'upcoming'→ hay próximo partido fuera de ventana
     *  - 'none'    → no hay partidos próximos
     *
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $now = now();

        // Matchday activo: kickoff entre -4h y +4h (en casa o fuera).
        $live = FootballMatch::query()
            ->whereBetween('kickoff_at', [$now->copy()->subHours(4), $now->copy()->addHours(4)])
            ->orderBy('kickoff_at')
            ->first();

        if ($live) {
            return [
                'state' => 'live',
                'match' => $live,
                'stats' => $this->buildLiveStats($live),
            ];
        }

        // Próximo partido en casa.
        $upcoming = FootballMatch::query()
            ->where('venue', 'home')
            ->where('kickoff_at', '>=', $now)
            ->orderBy('kickoff_at')
            ->first();

        if ($upcoming) {
            return [
                'state'    => 'upcoming',
                'match'    => $upcoming,
                'capacity' => $this->totalCapacity(),
            ];
        }

        return [
            'state' => 'none',
        ];
    }

    /**
     * @return array{total_scanned:int, total_capacity:int, percent:float, by_zone:array<int,array<string,mixed>>}
     */
    protected function buildLiveStats(FootballMatch $match): array
    {
        $totalCapacity = $this->totalCapacity();

        $totalScanned = Attendance::query()
            ->where('match_id', $match->id)
            ->count();

        // Aforo por zona: contamos accesos cuyo ticket pertenece a cada zona,
        // y la capacidad de la zona se calcula sumando sectors.capacity por slug.
        $scannedByZoneId = Attendance::query()
            ->where('attendances.match_id', $match->id)
            ->join('tickets', 'tickets.id', '=', 'attendances.ticket_id')
            ->whereNotNull('tickets.zone_id')
            ->select('tickets.zone_id', DB::raw('COUNT(*) as total'))
            ->groupBy('tickets.zone_id')
            ->pluck('total', 'tickets.zone_id')
            ->toArray();

        $capacityByZoneSlug = Sector::query()
            ->select('zone', DB::raw('SUM(capacity) as total'))
            ->whereNotNull('zone')
            ->groupBy('zone')
            ->pluck('total', 'zone')
            ->toArray();

        $byZone = Zone::query()
            ->orderBy('sort_order')
            ->get()
            ->map(function (Zone $z) use ($scannedByZoneId, $capacityByZoneSlug) {
                $scanned  = (int) ($scannedByZoneId[$z->id] ?? 0);
                $capacity = (int) ($capacityByZoneSlug[$z->slug] ?? $z->capacity_total ?? 0);
                $percent  = $capacity > 0 ? min(100.0, round(($scanned / $capacity) * 100, 1)) : 0.0;

                return [
                    'zone_id'  => $z->id,
                    'name'     => $z->name,
                    'color'    => $z->color ?: '#9CA3AF',
                    'scanned'  => $scanned,
                    'capacity' => $capacity,
                    'percent'  => $percent,
                ];
            })
            ->filter(fn ($row) => $row['capacity'] > 0 || $row['scanned'] > 0)
            ->values()
            ->all();

        $percentGlobal = $totalCapacity > 0
            ? min(100.0, round(($totalScanned / $totalCapacity) * 100, 1))
            : 0.0;

        return [
            'total_scanned'  => $totalScanned,
            'total_capacity' => $totalCapacity,
            'percent'        => $percentGlobal,
            'by_zone'        => $byZone,
        ];
    }

    protected function totalCapacity(): int
    {
        return (int) Sector::query()->sum('capacity');
    }
}
