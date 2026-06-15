<?php

namespace App\Console\Commands;

use App\Models\Seat;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Libera las butacas de abonados PENDIENTES que no renovaron a partir del
 * 28/06/2026. Los renovados tienen su butaca en 'sold'; los pendientes (que
 * mantuvieron su butaca reservada hasta esa fecha) están en 'reserved'. El
 * 28/06 esas 'reserved' que sigan sin renovar pasan a 'free' (a la venta).
 *
 * Seguro e idempotente: solo toca status='reserved'. Programado a diario; no
 * hace nada hasta el 28/06 (salvo --force).
 */
class LiberarAbonosPendientes extends Command
{
    protected $signature = 'abonos:liberar-pendientes {--force : libera ya, ignora la fecha} {--dry-run : solo informa}';

    protected $description = 'Libera (reserved->free) las butacas de abonados pendientes no renovados a partir del 28/06/2026.';

    public function handle(): int
    {
        $limite = Carbon::create(2026, 6, 28, 0, 0, 0);
        $hoy = Carbon::now();

        if (! $this->option('force') && $hoy->lt($limite)) {
            $this->info("Aún no es 28/06/2026 (hoy {$hoy->toDateString()}). No se libera nada. (Usa --force para forzar.)");
            return self::SUCCESS;
        }

        $count = Seat::where('status', 'reserved')->count();

        if ($this->option('dry-run')) {
            $this->info("[DRY-RUN] Se liberarían {$count} butacas (reserved -> free).");
            return self::SUCCESS;
        }

        if ($count === 0) {
            $this->info('No hay butacas reserved que liberar.');
            return self::SUCCESS;
        }

        $freed = Seat::where('status', 'reserved')->update(['status' => 'free']);
        Log::info("[abonos:liberar-pendientes] Liberadas {$freed} butacas reserved->free (abonados no renovados).");
        $this->info("Liberadas {$freed} butacas (reserved -> free). Ya están a la venta.");

        return self::SUCCESS;
    }
}
