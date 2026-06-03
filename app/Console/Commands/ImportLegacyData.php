<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\News;
use App\Models\Player;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Migrador legacy → schema Filament/Laravel actual.
 *
 * Lee de la conexión `legacy` (BD algeciras_legacy, dump del cliente)
 * y escribe en la conexión por defecto (algeciras_cf).
 *
 * REGLAS DE SEGURIDAD (no negociables):
 *  - NO se envía NINGÚN email
 *  - NO se dispara NINGÚN job en queue
 *  - NO se hace ninguna llamada a APIs externas
 *  - NO se loguean passwords ni tokens en stdout
 *  - Idempotente: si se ejecuta dos veces, no duplica gracias a legacy_*
 *
 * Uso:
 *   php artisan legacy:import --dry-run   # sólo cuenta, no inserta
 *   php artisan legacy:import             # ejecuta de verdad
 *   php artisan legacy:import --only=usuarios,abonos  # subset
 */
class ImportLegacyData extends Command
{
    protected $signature = 'legacy:import
        {--dry-run : Sólo muestra qué haría, no escribe nada}
        {--only= : Subset de tablas separadas por coma (usuarios,abonos,productos,noticias,partidos,jugadores,evento_partidos,notificaciones_partidos,clasificacion,jugador_stats)}';

    protected $description = 'Importa datos de la BD legacy algeciras_legacy hacia algeciras_cf con mapeo de schema';

    protected bool $dry = false;

    /** Map legacy_user_id -> customer_id (cacheado durante la corrida) */
    protected array $userMap = [];
    /** Map legacy_abono_id -> ticket_id */
    protected array $aboMap = [];
    /** Map legacy_partido_id -> match_id */
    protected array $matchMap = [];

    public function handle(): int
    {
        $this->dry = (bool) $this->option('dry-run');
        if ($this->dry) {
            $this->warn('═══ MODO DRY-RUN: no se escribirá nada ═══');
        }

        // Conexión legacy ad-hoc (la BD ya está creada en el MariaDB)
        config(['database.connections.legacy' => array_merge(
            config('database.connections.mariadb', config('database.connections.mysql', [])),
            ['database' => 'algeciras_legacy']
        )]);

        $only = $this->option('only');
        $tables = $only ? explode(',', $only) : [
            'usuarios','partidos','jugadores','productos','noticias',
            'abonos','evento_partidos','notificaciones_partidos',
            'clasificacion','jugador_stats',
        ];

        foreach ($tables as $t) {
            $method = 'import' . Str::studly(trim($t));
            if (! method_exists($this, $method)) {
                $this->warn("Skip $t (no method $method)");
                continue;
            }
            $this->line('');
            $this->info("▸ Importando $t ...");
            $start = microtime(true);
            try {
                $this->$method();
            } catch (\Throwable $e) {
                $this->error("✗ $t falló: " . $e->getMessage());
                $this->error($e->getTraceAsString());
                continue;
            }
            $secs = round(microtime(true) - $start, 2);
            $this->info("✓ $t completado en {$secs}s");
        }

        $this->line('');
        $this->info('═══ Importación legacy finalizada ═══');
        $this->resumen();
        return self::SUCCESS;
    }

    /**
     * usuarios → users + customers (1 a 1).
     * - Conserva password hash original.
     * - Marca legacy_user_id en customers para vincular abonos después.
     */
    protected function importUsuarios(): void
    {
        $rows = DB::connection('legacy')->table('usuarios')->get();
        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();
        $created = 0; $linked = 0;

        foreach ($rows as $u) {
            $bar->advance();

            $email = trim((string) $u->email);
            if (! $email) continue;

            // 1. User
            $user = User::where('email', $email)->first();
            if (! $user && ! $this->dry) {
                $user = User::create([
                    'name'              => (string) $u->nombre ?: $email,
                    'email'             => $email,
                    'password'          => (string) $u->password,  // hash bcrypt original — no se toca
                    'email_verified_at' => null,                    // no marcamos verificado
                    'profile_image'     => $u->profileImage ?? null,
                    'push_token'        => $u->expoPushToken ?? null,
                    'created_at'        => $this->parseDate($u->createdAt),
                    'updated_at'        => $this->parseDate($u->updatedAt),
                ]);
                $created++;
            }
            if (! $user) continue;

            // 2. Customer (uno por usuario; resto de datos vendrán de la primera fila de abonos con su email)
            $cust = Customer::where('user_id', $user->id)
                ->orWhere('email', $email)
                ->first();
            if (! $cust && ! $this->dry) {
                // Sólo metemos los campos que tenemos seguros en usuarios
                [$first, $last] = $this->splitNombre((string) $u->nombre);
                $cust = Customer::create([
                    'user_id'        => $user->id,
                    'first_name'     => $first ?: '—',
                    'last_name'      => $last ?: '',
                    'email'          => $email,
                    'phone'          => $u->telefono ?: null,
                    'dni'            => $u->dni ?: null,
                    'country'        => 'ES',
                    'is_socio'       => false,   // se reactivará al importar abonos si tiene uno activo
                    'legacy_user_id' => (int) $u->id,
                    'created_at'     => $this->parseDate($u->createdAt),
                    'updated_at'     => $this->parseDate($u->updatedAt),
                ]);
                $linked++;
            } elseif ($cust && ! $cust->legacy_user_id && ! $this->dry) {
                $cust->update(['legacy_user_id' => (int) $u->id]);
            }

            if ($user && $cust) {
                $this->userMap[(int) $u->id] = (int) $cust->id;
            }
        }

        $bar->finish();
        $this->line('');
        $this->info("  users creados: $created   customers vinculados: $linked");
    }

    /**
     * abonos → customers (completa datos) + tickets.
     * - El abono mezcla datos del titular (van a customers) + datos del abono (van a tickets).
     * - Si abonos.usuarioId existe en userMap, vinculamos al cust ya creado por usuarios.
     * - Si no, creamos un Customer nuevo SIN User (acceso solo por correo electrónico).
     */
    protected function importAbonos(): void
    {
        // Si --only=abonos se ejecuta sin usuarios, recargamos el mapping
        // desde la BD usando los legacy_user_id ya guardados en customers.
        if (empty($this->userMap)) {
            foreach (DB::table('customers')->whereNotNull('legacy_user_id')->get(['id', 'legacy_user_id']) as $c) {
                $this->userMap[(int) $c->legacy_user_id] = (int) $c->id;
            }
            $this->line("  Recargado userMap desde BD: " . count($this->userMap) . " customers vinculados");
        }

        $rows = DB::connection('legacy')->table('abonos')->get();
        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();
        $sinUsuario = 0; $creados = 0;

        // Producto "Abono legacy" placeholder (creamos uno único para enlazar todos los tickets importados)
        $productAbono = $this->ensureLegacyAbonoProduct();

        foreach ($rows as $a) {
            $bar->advance();

            // 1. Customer
            $custId = null;
            if ($a->usuarioId && isset($this->userMap[(int) $a->usuarioId])) {
                $custId = $this->userMap[(int) $a->usuarioId];
                // Actualizar campos del titular sólo si están vacíos
                if (! $this->dry) {
                    $cust = Customer::find($custId);
                    if ($cust) {
                        $cust->fill(array_filter([
                            'first_name'    => $cust->first_name && $cust->first_name !== '—' ? null : $a->nombre,
                            'last_name'     => $cust->last_name ?: $a->apellidos,
                            'dni'           => $cust->dni ?: $a->dni,
                            'phone'         => $cust->phone ?: $a->telefono,
                            'birth_date'    => $cust->birth_date ?: $this->parseDate($a->fechaNacimiento),
                            'address'       => $cust->address ?: $a->domicilio,
                            'city'          => $cust->city ?: $a->localidad,
                            'province'      => $cust->province ?: $a->provincia,
                            'postal_code'   => $cust->postal_code ?: $a->codigoPostal,
                            'country'       => $cust->country ?: $a->pais,
                            'gender'        => $cust->gender ?: $a->genero,
                            'is_socio'      => $a->activo ? true : ($cust->is_socio ?? false),
                            'socio_number'  => $cust->socio_number ?: ($a->codigoAbonado ? (string) $a->codigoAbonado : null),
                            'legacy_socio_id'=> $a->codigoAbonado ?: $cust->legacy_socio_id,
                        ]));
                        $cust->save();
                    }
                }
            } else {
                // Abono SIN usuarioId — creamos un customer sólo con datos del titular y SIN user account
                $sinUsuario++;
                if (! $this->dry) {
                    $cust = Customer::where('dni', $a->dni)
                        ->orWhere('email', $a->email)
                        ->first();
                    if (! $cust) {
                        $cust = Customer::create([
                            'first_name'    => $a->nombre ?: '—',
                            'last_name'     => $a->apellidos ?: '',
                            'email'         => $a->email ?: ('abono-legacy-' . $a->id . '@legacy.local'),
                            'phone'         => $a->telefono,
                            'dni'           => $a->dni,
                            'birth_date'    => $this->parseDate($a->fechaNacimiento),
                            'address'       => $a->domicilio,
                            'city'          => $a->localidad,
                            'province'      => $a->provincia,
                            'postal_code'   => $a->codigoPostal,
                            'country'       => $a->pais ?: 'ES',
                            'gender'        => $a->genero,
                            'is_socio'      => (bool) $a->activo,
                            'socio_number'  => $a->codigoAbonado ? (string) $a->codigoAbonado : null,
                            'legacy_socio_id'=> $a->codigoAbonado,
                            'created_at'    => $this->parseDate($a->createdAt),
                            'updated_at'    => $this->parseDate($a->updatedAt),
                        ]);
                    }
                    $custId = $cust->id;
                }
            }
            if (! $custId) continue;

            // 2. Ticket
            if ($this->dry) { $creados++; continue; }

            $exist = Ticket::where('legacy_id', (int) $a->id)->first();
            if ($exist) continue;

            $ticket = Ticket::create([
                'legacy_id'            => (int) $a->id,
                'customer_id'          => $custId,
                'product_id'           => $productAbono->id,
                'uuid'                 => (string) Str::uuid(),
                'qr_token'             => bin2hex(random_bytes(32)),
                'status'               => $a->activo ? 'issued' : 'expired',
                'holder_name'          => trim(($a->nombre ?? '') . ' ' . ($a->apellidos ?? '')),
                'holder_dni'           => $a->dni,
                'valid_from'           => $this->parseDate($a->fechaInicio),
                'valid_until'          => $this->parseDate($a->fechaFin),
                'price_paid'           => $a->precio,
                'legacy_codigo_acceso' => $a->codigoAcceso,
                'created_at'           => $this->parseDate($a->createdAt),
                'updated_at'           => $this->parseDate($a->updatedAt),
            ]);
            $this->aboMap[(int) $a->id] = $ticket->id;
            $creados++;
        }

        $bar->finish();
        $this->line('');
        $this->info("  tickets creados: $creados (de los cuales $sinUsuario abonos no tenían usuario asociado)");
    }

    protected function importPartidos(): void
    {
        $rows = DB::connection('legacy')->table('partidos')->get();
        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();
        $creados = 0;

        // Nuestra matches tiene fields distintos: opponent, opponent_logo, venue, kickoff_at, status, home_score, away_score
        // En legacy: equipoLocal, equipoVisitante, marcador, fecha, hora
        foreach ($rows as $p) {
            $bar->advance();
            if ($this->dry) { $creados++; continue; }

            $kickoff = $this->buildKickoff($p->fecha, $p->hora);
            // Asumimos que si equipoLocal contiene "algeciras" jugamos en casa
            $isHome = stripos((string) $p->equipoLocal, 'algeciras') !== false;
            $opponent = $isHome ? $p->equipoVisitante : $p->equipoLocal;
            $opponentLogo = $isHome ? $p->escudoVisitante : $p->escudoLocal;

            // Marcador "2-1"
            $home = $away = null;
            if ($p->marcador && preg_match('/(\d+)\s*-\s*(\d+)/', (string) $p->marcador, $m)) {
                $home = (int) $m[1];
                $away = (int) $m[2];
            }

            // No metemos season_id sin saber a cuál corresponde. Lo dejamos nullable y se completa luego en admin.
            $exists = DB::table('matches')
                ->where('opponent', $opponent)
                ->whereDate('kickoff_at', $kickoff->toDateString())
                ->first();
            if ($exists) {
                $this->matchMap[(int) $p->id] = $exists->id;
                continue;
            }

            $matchId = DB::table('matches')->insertGetId([
                'season_id'    => 1, // por defecto la temporada 1 (la actual). Admin lo corregirá si no.
                'opponent'     => (string) $opponent,
                'opponent_logo'=> $opponentLogo,
                'venue'        => $isHome ? 'home' : 'away',
                'stadium'      => $isHome ? 'Nuevo Mirador' : (string) $opponent,
                'kickoff_at'   => $kickoff,
                'status'       => ($home !== null) ? 'finished' : 'scheduled',
                'home_score'   => $home,
                'away_score'   => $away,
                'created_at'   => $this->parseDate($p->createdAt),
                'updated_at'   => $this->parseDate($p->updatedAt),
            ]);
            $this->matchMap[(int) $p->id] = $matchId;
            $creados++;
        }
        $bar->finish();
        $this->line('');
        $this->info("  matches creados: $creados (no duplica si ya existían por opponent+fecha)");
    }

    protected function importJugadores(): void
    {
        $rows = DB::connection('legacy')->table('jugadores')->get();
        $creados = 0;
        foreach ($rows as $j) {
            if ($this->dry) { $creados++; continue; }
            $exist = Player::where('full_name', $j->nombre)->orWhere('display_name', $j->nombreCorto)->first();
            if ($exist) continue;
            Player::create([
                'full_name'    => (string) $j->nombre,
                'display_name' => (string) ($j->nombreCorto ?: $j->nombre),
                'slug'         => Str::slug((string) ($j->nombreCorto ?: $j->nombre)) . '-leg' . $j->id,
                'position'     => (string) ($j->posicion ?? ''),
                'dorsal'       => $j->dorsal,
                'nationality'  => $j->nacionalidad,
                'photo'        => $j->foto,
                'height_cm'    => $j->altura,
                'active'       => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
            $creados++;
        }
        $this->info("  players creados: $creados");
    }

    protected function importProductos(): void
    {
        $rows = DB::connection('legacy')->table('productos')->get();
        $creados = 0;
        foreach ($rows as $p) {
            if ($this->dry) { $creados++; continue; }
            $existing = Product::where('legacy_id', (int) $p->id)->first();
            if ($existing) continue;
            Product::create([
                'legacy_id'         => (int) $p->id,
                'type'              => 'merch',
                'slug'              => Str::slug((string) $p->nombre) . '-leg' . $p->id,
                'name'              => (string) $p->nombre,
                'description'       => (string) ($p->descripcion ?? ''),
                'price'             => (float) $p->precio,
                'compare_at_price'  => $p->precioAnterior,
                'image'             => $p->imagen,
                'active'            => (bool) $p->activo,
                'featured'          => (bool) ($p->destacado ?? 0),
                'created_at'        => $this->parseDate($p->createdAt),
                'updated_at'        => $this->parseDate($p->updatedAt),
            ]);
            $creados++;
        }
        $this->info("  products creados: $creados");
    }

    protected function importNoticias(): void
    {
        $rows = DB::connection('legacy')->table('noticias')->get();
        $creados = 0;
        foreach ($rows as $n) {
            if ($this->dry) { $creados++; continue; }
            $slug = (string) ($n->slug ?: Str::slug((string) $n->titulo)) . '-leg' . $n->id;
            $exist = News::where('slug', $slug)->first();
            if ($exist) continue;
            News::create([
                'title'        => (string) $n->titulo,
                'slug'         => $slug,
                'excerpt'      => (string) ($n->extracto ?? ''),
                'body'         => (string) ($n->contenido ?? ''),
                'cover_image'  => $n->imagen,
                'category'     => $n->categoria ?: 'otro',
                // Solo se considera publicada si tenía activo=1; si no, sin published_at queda oculta.
                'published_at' => ($n->activo ?? 1) ? $this->parseDate($n->fecha) : null,
                'featured'     => (bool) ($n->destacado ?? 0),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
            $creados++;
        }
        $this->info("  news creadas: $creados");
    }

    protected function importEventoPartidos(): void
    {
        $rows = DB::connection('legacy')->table('evento_partidos')->get();
        $creados = 0;
        foreach ($rows as $e) {
            if ($this->dry) { $creados++; continue; }
            $matchId = $this->matchMap[(int) $e->partidoId] ?? null;
            if (! $matchId) continue;
            $exist = DB::table('match_events')->where('legacy_id', (int) $e->id)->exists();
            if ($exist) continue;
            DB::table('match_events')->insert([
                'legacy_id'         => (int) $e->id,
                'football_match_id' => $matchId,
                'minute'            => $e->minuto,
                'type'              => (string) $e->tipo,
                'player_name'       => $e->jugador,
                'player_in'         => $e->entra,
                'player_out'        => $e->sale,
                'team'              => $e->equipo,
                'image'             => $e->imagen,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
            $creados++;
        }
        $this->info("  match_events creados: $creados");
    }

    protected function importNotificacionesPartidos(): void
    {
        $rows = DB::connection('legacy')->table('notificaciones_partidos')->get();
        $creados = 0;
        foreach ($rows as $n) {
            if ($this->dry) { $creados++; continue; }
            $ticketId = $this->aboMap[(int) $n->abonoId] ?? null;
            $matchId  = $this->matchMap[(int) $n->partidoId] ?? null;
            $exist = DB::table('match_notifications')->where('legacy_id', (int) $n->id)->exists();
            if ($exist) continue;
            DB::table('match_notifications')->insert([
                'legacy_id'         => (int) $n->id,
                'ticket_id'         => $ticketId,
                'football_match_id' => $matchId,
                'sent_at'           => $this->parseDate($n->enviadoAt),
                'channel'           => $n->canal,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
            $creados++;
        }
        $this->info("  match_notifications creados: $creados");
    }

    protected function importClasificacion(): void
    {
        $rows = DB::connection('legacy')->table('clasificacion')->get();
        $creados = 0;
        foreach ($rows as $c) {
            if ($this->dry) { $creados++; continue; }
            $exist = DB::table('classifications')->where('legacy_id', (int) $c->id)->exists();
            if ($exist) continue;
            DB::table('classifications')->insert([
                'legacy_id'    => (int) $c->id,
                'position'     => $c->posicion,
                'team_name'    => $c->equipo,
                'team_logo'    => $c->escudo,
                'played'       => (int) ($c->pj ?? 0),
                'goals_for'    => (int) ($c->gf ?? 0),
                'goals_against'=> (int) ($c->gc ?? 0),
                'points'       => (int) ($c->puntos ?? 0),
                'season_string'=> '2024-25',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
            $creados++;
        }
        $this->info("  classifications creados: $creados");
    }

    protected function importJugadorStats(): void
    {
        $rows = DB::connection('legacy')->table('jugador_stats')->get();
        $creados = 0;
        foreach ($rows as $j) {
            if ($this->dry) { $creados++; continue; }
            $exist = DB::table('player_stats')->where('legacy_id', (int) $j->id)->exists();
            if ($exist) continue;
            // Necesitamos mapear el jugadorId legacy a un player_id. Por simplicidad
            // saltamos las stats si no podemos resolver — el operador puede importarlas
            // luego desde admin.
            DB::table('player_stats')->insert([
                'legacy_id'      => (int) $j->id,
                'player_id'      => 1, // placeholder — admin lo corregirá
                'season_string'  => (string) ($j->temporada ?: '2024-25'),
                'goals'          => $j->goles ?? 0,
                'assists'        => $j->asistencias ?? 0,
                'minutes_played' => $j->minutosJugados ?? 0,
                'appearances'    => $j->partidos ?? 0,
                'starting_xi'    => $j->titularidades ?? 0,
                'yellow_cards'   => $j->tarjetasAmarillas ?? 0,
                'red_cards'      => $j->tarjetasRojas ?? 0,
                'extra'          => json_encode((array) $j),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            $creados++;
        }
        $this->info("  player_stats creados: $creados");
    }

    // --- Helpers ---

    protected function ensureLegacyAbonoProduct(): Product
    {
        $p = Product::where('slug', 'abono-temporada-legacy')->first();
        if ($p) return $p;
        if ($this->dry) {
            return new Product(['id' => 0]);
        }
        return Product::create([
            'type'  => 'abono',
            'sku'   => 'ABONO-LEGACY',
            'slug'  => 'abono-temporada-legacy',
            'name'  => 'Abono temporada (legacy)',
            'description' => 'Abono importado de la BD del club anterior.',
            'price' => 0,
            'active'=> false, // no se vende online — solo histórico
        ]);
    }

    protected function splitNombre(string $nombre): array
    {
        $nombre = trim($nombre);
        if (! str_contains($nombre, ' ')) return [$nombre, ''];
        $parts = explode(' ', $nombre, 2);
        return [$parts[0], $parts[1] ?? ''];
    }

    protected function parseDate($value): ?Carbon
    {
        if (! $value) return null;
        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function buildKickoff($fecha, $hora): Carbon
    {
        try {
            $base = Carbon::parse((string) $fecha);
            if ($hora) {
                $parts = explode(':', (string) $hora);
                $base->setTime((int) ($parts[0] ?? 0), (int) ($parts[1] ?? 0));
            }
            return $base;
        } catch (\Throwable $e) {
            return now();
        }
    }

    protected function resumen(): void
    {
        $this->table(['Tabla', 'Filas en algeciras_cf'], [
            ['users',               DB::table('users')->count()],
            ['customers',           DB::table('customers')->count()],
            ['customers con socio', DB::table('customers')->where('is_socio', 1)->count()],
            ['tickets (abonos)',    DB::table('tickets')->whereNotNull('legacy_id')->count()],
            ['matches',             DB::table('matches')->count()],
            ['players',             DB::table('players')->count()],
            ['products',            DB::table('products')->count()],
            ['news',                DB::table('news')->count()],
            ['match_events',        DB::table('match_events')->count()],
            ['match_notifications', DB::table('match_notifications')->count()],
            ['classifications',     DB::table('classifications')->count()],
            ['player_stats',        DB::table('player_stats')->count()],
        ]);
    }
}
