<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Customer;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\NotificationPreference;
use App\Services\MatchdayService;

/**
 * Controller del Área Personal pública (web).
 *
 * Usa el guard `web` (sesión cookie de Laravel) — el mismo User+Customer
 * que la API usa con Sanctum tokens. Resultado: la cuenta del socio
 * funciona en web Y app móvil con las mismas credenciales.
 */
class AreaPersonalController extends Controller
{
    // ------------------------------------------------------------------
    // AUTH (públicas)
    // ------------------------------------------------------------------

    /** GET /area-personal — login si no auth, dashboard si auth.
     *  Si HOY hay partido en casa Y el user tiene customer, el área se
     *  reemplaza por la vista matchday (a no ser que se pase ?force_dashboard=1).
     */
    public function index(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user()->load('customer');
            $customer = $user->customer;

            if ($customer && ! $request->boolean('force_dashboard')) {
                try {
                    $service = app(MatchdayService::class);
                    if ($service->isMatchday()) {
                        $banner = $service->matchdayBannerFor($customer);
                        if ($banner) {
                            return view('pages.area-personal.matchday', array_merge(
                                $banner,
                                ['user' => $user, 'customer' => $customer]
                            ));
                        }
                    }
                } catch (\Throwable $e) {
                    // Si algo del MatchdayService peta no rompemos el área personal
                    \Illuminate\Support\Facades\Log::warning('MatchdayService error en index area-personal: '.$e->getMessage());
                }
            }

            return $this->resumen($request);
        }

        return view('pages.area-personal');
    }

    /** POST /area-personal/login */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'nullable',
        ]);

        $remember = (bool) ($data['remember'] ?? false);
        if (Auth::attempt(['email' => $data['email'], 'password' => $data['password']], $remember)) {
            $request->session()->regenerate();
            Auth::user()->update(['last_login_at' => now()]);
            return redirect()->intended(route('area-personal'));
        }

        return back()->withErrors(['email' => 'Credenciales incorrectas.'])->onlyInput('email');
    }

    /** POST /area-personal/register */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:120',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'phone'    => 'nullable|string|max:32',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Customer::create([
            'user_id' => $user->id,
            'email' => $data['email'],
            'first_name' => $data['name'],
            'phone' => $data['phone'] ?? null,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('area-personal');
    }

    /** POST /area-personal/logout */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    // ------------------------------------------------------------------
    // PROTEGIDAS (requieren Auth::check)
    // ------------------------------------------------------------------

    /** Helper: garantiza auth o redirect. Devuelve [user, customer]. */
    protected function ensureAuth()
    {
        if (! Auth::check()) {
            abort(redirect()->route('area-personal'));
        }
        $user = Auth::user()->load('customer');
        return [$user, $user->customer];
    }

    /** Datos comunes para la sidebar (contadores). */
    protected function sidebarData($customer): array
    {
        if (! $customer) {
            return [
                'count_abonos'    => 0,
                'count_entradas'  => 0,
                'count_compras'   => 0,
                'count_cupones'   => 0,
            ];
        }

        $tickets = $customer->tickets()->with('product')->get();
        $abonos   = $tickets->filter(fn ($t) => optional($t->product)->type === 'abono');
        $entradas = $tickets->filter(fn ($t) => optional($t->product)->type === 'entrada');

        return [
            'count_abonos'   => $abonos->count(),
            'count_entradas' => $entradas->count(),
            'count_compras'  => $customer->orders()->count(),
            'count_cupones'  => $customer->customerCoupons()->where('status', 'available')->count(),
        ];
    }

    /** GET /area-personal (auth) — resumen tipo dashboard */
    public function resumen(Request $request)
    {
        [$user, $customer] = $this->ensureAuth();

        $abonos = collect();
        $entradas = collect();
        $cuponesDisponibles = 0;
        $proximoPartido = null;
        $asistencia = 0.0;
        $votosMvp = 0;

        if ($customer) {
            $tickets = $customer->tickets()->with('product', 'zone', 'order')->get();
            $abonos   = $tickets->filter(fn ($t) => optional($t->product)->type === 'abono');
            $entradas = $tickets->filter(fn ($t) => optional($t->product)->type === 'entrada');
            $cuponesDisponibles = $customer->customerCoupons()->where('status', 'available')->count();
            try {
                $proximoPartido = \App\Models\FootballMatch::upcoming()->first();
            } catch (\Throwable $e) {
                $proximoPartido = null;
            }
            try {
                $asistencia = $customer->attendance_rate ?? 0;
            } catch (\Throwable $e) {
                $asistencia = 0;
            }
            $votosMvp = $customer->mvpVotes()->count();
        }

        $sidebar = $this->sidebarData($customer);

        // Banner pequeño "Hoy hay partido" para inyectar en el resumen.
        $matchdayBanner = null;
        try {
            $service = app(MatchdayService::class);
            if ($service->isMatchday()) {
                $matchdayBanner = $service->todaysHomeMatch();
            }
        } catch (\Throwable $e) {
            $matchdayBanner = null;
        }

        return view('pages.area-personal.resumen', array_merge([
            'user' => $user,
            'customer' => $customer,
            'abonos' => $abonos,
            'entradas' => $entradas,
            'cuponesDisponibles' => $cuponesDisponibles,
            'proximoPartido' => $proximoPartido,
            'asistencia' => $asistencia,
            'votosMvp' => $votosMvp,
            'matchdayBanner' => $matchdayBanner,
        ], $sidebar));
    }

    /** GET /area-personal/carnet */
    public function carnet(Request $request)
    {
        [$user, $customer] = $this->ensureAuth();
        $sidebar = $this->sidebarData($customer);

        return view('pages.area-personal.carnet', array_merge([
            'user' => $user,
            'customer' => $customer,
        ], $sidebar));
    }

    /** GET /area-personal/abonos */
    public function abonos(Request $request)
    {
        [$user, $customer] = $this->ensureAuth();

        $abonos = collect();
        if ($customer) {
            $abonos = $customer->tickets()
                ->with('product', 'zone', 'season')
                ->whereHas('product', fn ($q) => $q->where('type', 'abono'))
                ->get();
        }

        $sidebar = $this->sidebarData($customer);

        return view('pages.area-personal.abonos', array_merge([
            'user' => $user,
            'customer' => $customer,
            'abonos' => $abonos,
        ], $sidebar));
    }

    /** GET /area-personal/entradas */
    public function entradas(Request $request)
    {
        [$user, $customer] = $this->ensureAuth();

        $activas = collect();
        $historico = collect();
        if ($customer) {
            $entradas = $customer->tickets()
                ->with('product', 'zone', 'match')
                ->whereHas('product', fn ($q) => $q->where('type', 'entrada'))
                ->get();

            $activas = $entradas->filter(function ($t) {
                $match = $t->match;
                if (! $match) return $t->status === 'issued';
                return optional($match->kickoff_at)->gte(now());
            });
            $historico = $entradas->diff($activas);
        }

        $sidebar = $this->sidebarData($customer);

        return view('pages.area-personal.entradas', array_merge([
            'user' => $user,
            'customer' => $customer,
            'activas' => $activas,
            'historico' => $historico,
        ], $sidebar));
    }

    /** GET /area-personal/compras */
    public function compras(Request $request)
    {
        [$user, $customer] = $this->ensureAuth();

        $orders = collect();
        if ($customer) {
            $orders = $customer->orders()
                ->orderByDesc('created_at')
                ->get();
        }

        $sidebar = $this->sidebarData($customer);

        return view('pages.area-personal.compras', array_merge([
            'user' => $user,
            'customer' => $customer,
            'orders' => $orders,
        ], $sidebar));
    }

    /** GET /area-personal/compras/{reference} */
    public function compraDetalle(Request $request, $reference)
    {
        [$user, $customer] = $this->ensureAuth();

        if (! $customer) {
            abort(404);
        }

        $order = Order::with('items.product', 'tickets.product', 'tickets.zone')
            ->where('reference', $reference)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $sidebar = $this->sidebarData($customer);

        return view('pages.area-personal.compra-detalle', array_merge([
            'user' => $user,
            'customer' => $customer,
            'order' => $order,
        ], $sidebar));
    }

    /** GET /area-personal/beneficios */
    public function beneficios(Request $request)
    {
        [$user, $customer] = $this->ensureAuth();

        $disponibles = collect();
        $canjeados = collect();
        $sugeridos = collect();

        if ($customer) {
            $disponibles = $customer->customerCoupons()
                ->with('coupon')
                ->where('status', 'available')
                ->get();

            $canjeados = $customer->customerCoupons()
                ->with('coupon')
                ->where('status', 'redeemed')
                ->orderByDesc('redeemed_at')
                ->get();

            // Cupones públicos del tier (sin pivot todavía) — sugeridos
            $tier = $customer->tier ?? 'aficionado';
            $usadosIds = $customer->customerCoupons()->pluck('coupon_id')->toArray();
            try {
                $sugeridos = Coupon::active()
                    ->forTier($tier)
                    ->whereNotIn('id', $usadosIds)
                    ->get()
                    ->filter(fn ($c) => $c->isValid());
            } catch (\Throwable $e) {
                $sugeridos = collect();
            }
        }

        $sidebar = $this->sidebarData($customer);

        return view('pages.area-personal.beneficios', array_merge([
            'user' => $user,
            'customer' => $customer,
            'disponibles' => $disponibles,
            'canjeados' => $canjeados,
            'sugeridos' => $sugeridos,
        ], $sidebar));
    }

    /** GET /area-personal/actividad */
    public function actividad(Request $request)
    {
        [$user, $customer] = $this->ensureAuth();

        $votos = collect();
        $asistencias = collect();

        if ($customer) {
            $votos = $customer->mvpVotes()
                ->with('player', 'match')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();

            $asistencias = $customer->matchAttendances()
                ->with('match')
                ->orderByDesc('checked_in_at')
                ->limit(50)
                ->get();
        }

        $sidebar = $this->sidebarData($customer);

        return view('pages.area-personal.actividad', array_merge([
            'user' => $user,
            'customer' => $customer,
            'votos' => $votos,
            'asistencias' => $asistencias,
        ], $sidebar));
    }

    /** GET /area-personal/datos */
    public function datos(Request $request)
    {
        [$user, $customer] = $this->ensureAuth();
        $sidebar = $this->sidebarData($customer);

        return view('pages.area-personal.datos', array_merge([
            'user' => $user,
            'customer' => $customer,
        ], $sidebar));
    }

    /** POST /area-personal/datos */
    public function actualizarDatos(Request $request)
    {
        [$user, $customer] = $this->ensureAuth();

        $data = $request->validate([
            'name'         => 'required|string|max:120',
            'first_name'   => 'nullable|string|max:80',
            'last_name'    => 'nullable|string|max:80',
            'phone'        => 'nullable|string|max:32',
            'dni'          => 'nullable|string|max:32',
            'birth_date'   => 'nullable|date',
            'address'      => 'nullable|string|max:255',
            'city'         => 'nullable|string|max:120',
            'province'     => 'nullable|string|max:120',
            'postal_code'  => 'nullable|string|max:16',
            'country'      => 'nullable|string|max:80',
            'language'     => 'nullable|string|max:8',
        ]);

        $user->update(['name' => $data['name']]);

        if ($customer) {
            $customer->update(collect($data)->except(['name'])->toArray());
        } else {
            // Crear customer si no existe (caso raro tras registro antiguo)
            $customer = Customer::create(array_merge(
                ['user_id' => $user->id, 'email' => $user->email],
                collect($data)->except(['name'])->toArray()
            ));
        }

        return redirect()->route('area-personal.datos')->with('status', 'Datos actualizados correctamente.');
    }

    /** POST /area-personal/cambiar-password */
    public function cambiarPassword(Request $request)
    {
        [$user] = $this->ensureAuth();

        $data = $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }

        $user->update(['password' => Hash::make($data['password'])]);

        return redirect()->route('area-personal.datos')->with('status', 'Contraseña actualizada correctamente.');
    }

    /** GET /area-personal/notificaciones */
    public function notificaciones(Request $request)
    {
        [$user, $customer] = $this->ensureAuth();
        $sidebar = $this->sidebarData($customer);

        $categories = NotificationPreference::categories();
        $prefs = collect();
        if ($customer) {
            $prefs = $customer->notificationPreferences()->get()->keyBy('category');
        }

        return view('pages.area-personal.notificaciones', array_merge([
            'user' => $user,
            'customer' => $customer,
            'categories' => $categories,
            'prefs' => $prefs,
        ], $sidebar));
    }

    /** POST /area-personal/notificaciones */
    public function actualizarNotificaciones(Request $request)
    {
        [$user, $customer] = $this->ensureAuth();

        if (! $customer) {
            return back()->withErrors(['general' => 'No tienes ficha de cliente. Contacta con el club.']);
        }

        $input = $request->input('prefs', []);
        $categories = array_keys(NotificationPreference::categories());

        foreach ($categories as $category) {
            $email = (bool) ($input[$category]['email'] ?? false);
            $push  = (bool) ($input[$category]['push']  ?? false);

            NotificationPreference::updateOrCreate(
                ['customer_id' => $customer->id, 'category' => $category],
                ['email_enabled' => $email, 'push_enabled' => $push]
            );
        }

        return redirect()->route('area-personal.notificaciones')->with('status', 'Preferencias guardadas.');
    }
}
