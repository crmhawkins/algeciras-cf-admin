<?php

namespace Database\Seeders;

use App\Models\Seat;
use App\Models\Sector;
use Illuminate\Database\Seeder;

/**
 * Generador de butacas para cada sector disponible.
 *
 * Lógica trapezoidal: 7 filas (preferente/fondo) o 14 filas (tribuna),
 * número de asientos por fila crece desde fila 1 hasta la última.
 *
 * Numeración: par/impar según el `parity` del sector, partiendo de un
 * offset global por zona + número.
 *
 * Estado: random según ratio capacity/total para reflejar las plazas
 * libres reales del scrape de compralaentrada.com.
 */
class SeatsSeeder extends Seeder
{
    public function run(): void
    {
        Seat::query()->delete();

        foreach (Sector::available()->get() as $sector) {
            $config = $this->configForSector($sector);
            $seats = $this->generateSeats($sector, $config);

            // Marcar `n` aleatorios como sold para que coincida ~ con capacity (plazas libres)
            $toBlock = max(0, $seats->count() - $sector->capacity);
            $blockedIds = $seats->shuffle()->take($toBlock)->pluck('id');

            if ($blockedIds->isNotEmpty()) {
                Seat::whereIn('id', $blockedIds)->update(['status' => 'sold']);
            }
        }
    }

    private function configForSector(Sector $sector): array
    {
        // Filas según zona
        $rows = match ($sector->zone) {
            'tribuna_alta', 'tribuna_baja' => 14,
            'preferente'                   => 7,
            'fondo_norte', 'fondo_sur'     => 10,
            default                        => 7,
        };

        // Asientos por fila (trapezoidal: primera más corta, última más larga)
        $minPerRow = match ($sector->zone) {
            'tribuna_alta', 'tribuna_baja' => 8,
            'preferente'                   => 10,
            'fondo_norte', 'fondo_sur'     => 10,
            default                        => 8,
        };

        // Offset base de numeración
        // Estimación: cada zona ocupa un rango, los sectores se reparten
        $zoneBase = match ($sector->zone) {
            'tribuna_alta' => 1,    // 1, 31, 61, ... (cada sector +30)
            'tribuna_baja' => 401,  // 401, 431, ...
            'preferente'   => 101,  // 101, 131, ...
            'fondo_norte'  => 601,
            'fondo_sur'    => 701,
            default        => 1,
        };
        // Sólo tomar number si es numérico (saltar 'A', 'B', null)
        $sectorNum = is_numeric($sector->number) ? max(1, (int) $sector->number) : 1;
        $baseSeat = max(1, $zoneBase + ($sectorNum - 1) * 30);

        // Si es par, ajustamos para que el primer asiento sea par
        if ($sector->parity === 'par' && $baseSeat % 2 !== 0) $baseSeat++;
        if ($sector->parity === 'impar' && $baseSeat % 2 === 0) $baseSeat++;

        return [
            'rows'       => $rows,
            'min_per_row'=> $minPerRow,
            'base_seat'  => $baseSeat,
        ];
    }

    private function generateSeats(Sector $sector, array $config)
    {
        $created = collect();
        $rows = $config['rows'];
        $minPerRow = $config['min_per_row'];
        $baseSeat = $config['base_seat'];

        for ($row = 1; $row <= $rows; $row++) {
            // Cada fila tiene minPerRow + (row-1) asientos (forma trapezoidal hacia atrás)
            $seatsInRow = $minPerRow + ($row - 1);
            $step = $sector->parity === 'none' ? 1 : 2;

            for ($i = 0; $i < $seatsInRow; $i++) {
                $number = $baseSeat + ($i * $step);
                $seat = Seat::create([
                    'sector_id' => $sector->id,
                    'row'       => $row,
                    'number'    => $number,
                    'status'    => 'free',
                ]);
                $created->push($seat);
            }
        }

        return $created;
    }
}
