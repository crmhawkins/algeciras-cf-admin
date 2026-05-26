<?php

namespace Database\Seeders;

use App\Models\Seat;
use App\Models\Sector;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Generador de butacas usando la disposición REAL extraída del API de
 * compralaentrada.com (vía /api1/f/zonas/{id}?conf=true).
 *
 * Para cada sector, el sitio oficial expone:
 *   rows          → número de filas
 *   seats_row     → butacas por fila (constante en todas las filas)
 *   initial_row   → primer fila (etiqueta inicial)
 *   initial_seat  → primer número de butaca
 *   direction     → 1 (orden ascendente L→R) o 2 (descendente L→R, sector espejado)
 *
 * Numeración: SIEMPRE step +2 (par o impar según initial_seat). El estadio
 * Nuevo Mirador numera todas las gradas en par/impar.
 *
 * Estado: aleatorio según ratio libres/aforo del scrape (refleja plazas
 * realmente disponibles en el momento del scrape).
 */
class SeatsSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('data/sectors_layout.json');

        if (! File::exists($jsonPath)) {
            $this->command->error("No se encuentra {$jsonPath}");
            return;
        }

        $layouts = json_decode(File::get($jsonPath), true);
        $layoutsBySvgRegion = collect($layouts)->keyBy('id');

        Seat::query()->delete();

        $totalCreated = 0;
        $totalSold = 0;

        foreach (Sector::all() as $sector) {
            $layout = $layoutsBySvgRegion->get($sector->svg_region);

            if (! $layout) {
                $this->command->warn("Sector {$sector->name} (svg_region={$sector->svg_region}) sin layout — omitido.");
                continue;
            }

            $rowsCount  = (int) $layout['rows'];
            $seatsRow   = (int) $layout['seats_row'];
            $initialRow = (int) $layout['initial_row'];
            $initialSeat= (int) $layout['initial_seat'];
            $direction  = (int) ($layout['direction'] ?? 1);
            $aforo      = (int) ($layout['aforo'] ?? ($rowsCount * $seatsRow));
            $libres     = (int) ($layout['libres'] ?? $aforo);

            if ($rowsCount === 0 || $seatsRow === 0) {
                continue; // sector virtual (Simpatizantes, Baby, Socio de Honor, etc.)
            }

            // Parsear butacas ocultas (forma trapezoidal/irregular): "row-seat,row-seat,..."
            $hiddenStr = (string) ($layout['hidden'] ?? '');
            $hidden    = collect(explode(',', $hiddenStr))
                ->filter()
                ->mapWithKeys(function ($pair) {
                    [$r, $s] = explode('-', trim($pair));
                    return ["{$r}-{$s}" => true];
                });

            $seatsForSector = collect();

            for ($r = 0; $r < $rowsCount; $r++) {
                $rowLabel = $initialRow + $r;
                for ($s = 0; $s < $seatsRow; $s++) {
                    // step SIEMPRE +2 (par/impar según initial_seat)
                    $number = $initialSeat + ($s * 2);

                    // Saltar butacas en la lista de ocultos (sector trapezoidal o con huecos)
                    if ($hidden->has("{$rowLabel}-{$number}")) {
                        continue;
                    }

                    $seat = Seat::create([
                        'sector_id' => $sector->id,
                        'row'       => $rowLabel,
                        'number'    => $number,
                        'status'    => 'free',
                    ]);
                    $seatsForSector->push($seat);
                    $totalCreated++;
                }
            }

            // Marcar como `sold` el ratio que corresponda según `libres/aforo`
            $toBlock = max(0, $seatsForSector->count() - $libres);
            if ($toBlock > 0) {
                $blockedIds = $seatsForSector->shuffle()->take($toBlock)->pluck('id');
                Seat::whereIn('id', $blockedIds)->update(['status' => 'sold']);
                $totalSold += $toBlock;
            }

            $this->command->info(
                sprintf("✓ %-30s %2d filas × %2d butacas (%3d libres / %3d total) dir=%d",
                    $sector->name, $rowsCount, $seatsRow, $libres, $aforo, $direction
                )
            );
        }

        $this->command->info("");
        $this->command->info("Total: {$totalCreated} butacas creadas, {$totalSold} marcadas como sold.");
    }
}
