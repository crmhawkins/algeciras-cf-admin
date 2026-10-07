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

            // Si el usuario venía de /comprar-directo y le mandamos al
            // login, reanudamos su compra automáticamente.
            $resume = $request->session()->pull('intended_purchase');
            if ($resume) {
                return redirect($resume);
            }

            return redirect()->intended(route('area-personal'));
        }

        return back()->withErrors(['email' => 'Credenciales incorrectas.'])->onlyInput('email');
    }

    /** POST /area-personal/register */
    public function register(Request $request)
    {
        // Acepta tanto el form con `name` (single field) como con
        // `first_name` + `last_name` (form más completo). DNI opcional pero
        // valida unicidad si llega.
        $request->merge([
            'name'       => $request->input('name', trim(
                $request->input('first_name', '') . ' ' . $request->input('last_name', '')
            )) ?: null,
        ]);

        // Registro completo (regla del cliente 2026-06-02): todos los datos
        // identificativos del socio son obligatorios — el club necesita
        // nombre completo, DNI, dirección física, teléfono, email y fecha
        // de nacimiento para emitir el carnet y cumplir LOPD/AEAT.
        $data = $request->validate([
            'name'        => 'required|string|max:120',
            'first_name'  => 'required|string|max:80',
            'last_name'   => 'required|string|max:80',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:6|confirmed',
            'phone'       => 'required|string|min:9|max:32',
            'dni'         => 'required|string|max:24|unique:customers,dni',
            'birth_date'  => ['required', 'date', 'before:'.now()->subYears(14)->format('Y-m-d'), 'after:'.now()->subYears(110)->format('Y-m-d')],
            'address'     => 'required|string|max:255',
            'city'        => 'required|string|max:120',
            'postal_code' => 'required|string|regex:/^[0-9]{5}$/',
            'province'    => 'nullable|string|max:120',
        ], [
            'email.unique'        => 'Ya existe una cuenta con este email. Inicia sesión.',
            'dni.unique'          => 'Ya existe una cuenta con este DNI.',
            'dni.required'        => 'El DNI es obligatorio.',
            'phone.required'      => 'El teléfono es obligatorio.',
            'phone.min'           => 'El teléfono no es válido.',
            'birth_date.required' => 'La fecha de nacimiento es obligatoria.',
            'birth_date.before'   => 'Debes ser mayor de 14 años.',
            'birth_date.after'    => 'Fecha de nacimiento no válida.',
            'address.required'    => 'La dirección es obligatoria.',
            'city.required'       => 'La ciudad es obligatoria.',
            'postal_code.required'=> 'El código postal es obligatorio.',
            'postal_code.regex'   => 'El código postal debe tener 5 dígitos.',
            'first_name.required' => 'El nombre es obligatorio.',
            'last_name.required'  => 'Los apellidos son obligatorios.',
            'password.min'        => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed'  => 'Las contraseñas no coinciden.',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // first_name + last_name por separado si llegan, si no hereda de name.
        $first = $data['first_name'] ?? null;
        $last  = $data['last_name']  ?? null;
        if (!$first && !$last) {
            $parts = preg_split('/\s+/', trim($data['name']), 2);
            $first = $parts[0] ?? $data['name'];
            $last  = $parts[1] ?? '';
        }

        Customer::create([
            'user_id'     => $user->id,
            'email'       => $data['email'],
            'first_name'  => $first,
            'last_name'   => $last ?: '',  // ← columna NOT NULL en BD
            'phone'       => $data['phone'] ?? null,
            'dni'         => $data['dni']   ?? null,
            'birth_date'  => $data['birth_date'] ?? null,
            'address'     => $data['address']    ?? null,
            'city'        => $data['city']       ?? null,
            'postal_code' => $data['postal_code']?? null,
            'province'    => $data['province']   ?? 'Cádiz',
            'country'     => 'ES',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        // Reanudar compra interrumpida si la había.
        $resume = $request->session()->pull('intended_purchase');
        if ($resume) {
            return redirect($resume);
        }

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
            // Ticket no tiene relación `order` directa (va por orderItem). Cargamos solo lo necesario.
            $tickets = $customer->tickets()->with('product', 'zone')->get();
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

    /** POST /area-personal/foto — subir foto perfil del socio.
     *
     * Reescala SIEMPRE a 600×600 max (manteniendo aspect ratio) y convierte
     * a JPEG calidad 85. Una foto de iPhone 12MP (~6 MB) acabaría pesando
     * ~80 kB y carga al instante en el carnet. Esto:
     *  - Evita el 500 que sufría el usuario al subir fotos grandes (GD
     *    se quedaba sin memoria leyendo el original).
     *  - Mantiene el storage del servidor pequeño.
     *  - Carga rápido en el carnet/sidebar.
     */
    public function subirFoto(Request $request)
    {
        [$user] = $this->ensureAuth();

        $data = $request->validate([
            'profile_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:20480', // 20MB de subida (luego se reescala)
        ], [
            'profile_image.required' => 'Selecciona una imagen.',
            'profile_image.image'    => 'El archivo debe ser una imagen.',
            'profile_image.mimes'    => 'Formatos válidos: JPG, PNG o WEBP.',
            'profile_image.max'      => 'La imagen no puede pesar más de 20MB.',
        ]);

        $upload = $data['profile_image'];

        try {
            // Reescalar con GD nativo (viene con PHP) — no necesita
            // intervention/image ni más composer deps.
            $resized = $this->reescalarImagenCuadrada($upload->getRealPath(), 600, 85);
        } catch (\Throwable $e) {
            \Log::warning('subirFoto reescalado fallo', [
                'user' => $user->id,
                'err'  => $e->getMessage(),
            ]);
            return back()->withErrors([
                'profile_image' => 'No se pudo procesar la imagen. Prueba con otra (JPG/PNG, menos de 20MB).',
            ]);
        }

        // Borramos foto previa si existía (evitamos basura en storage).
        if ($user->profile_image) {
            try {
                \Illuminate\Support\Facades\Storage::disk('public')
                    ->delete($user->profile_image);
            } catch (\Throwable $e) { /* silent */ }
        }

        $relativePath = 'avatars/'.$user->id.'-'.\Illuminate\Support\Str::random(8).'.jpg';
        \Illuminate\Support\Facades\Storage::disk('public')->put($relativePath, $resized);

        $user->update(['profile_image' => $relativePath]);

        return back()->with('status', 'Foto de perfil actualizada.');
    }

    /**
     * Lee un archivo de imagen, lo reescala a un cuadrado de $size×$size
     * (recortando lo necesario para mantener proporción 1:1 — ideal para
     * carnet) y lo devuelve como binario JPEG.
     */
    protected function reescalarImagenCuadrada(string $sourcePath, int $size = 600, int $quality = 85): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new \RuntimeException('GD no disponible en este PHP — no podemos procesar imágenes.');
        }

        // Sube el memory_limit puntualmente para fotos grandes (default
        // 256M en el container ya es suficiente, esto es un cinturón extra).
        @ini_set('memory_limit', '512M');

        $info = @getimagesize($sourcePath);
        if ($info === false) {
            throw new \RuntimeException('No es una imagen válida.');
        }
        [$w, $h, $type] = $info;

        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG  => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default        => false,
        };
        if (! $src) {
            throw new \RuntimeException('Formato no soportado.');
        }

        // Corregir orientación EXIF si aplica (iPhone fotos vienen rotadas).
        if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($sourcePath);
            $ori  = $exif['Orientation'] ?? null;
            if (in_array($ori, [3, 6, 8], true) && function_exists('imagerotate')) {
                $angle = ['3' => 180, '6' => -90, '8' => 90][(string) $ori] ?? 0;
                if ($angle) {
                    $src = imagerotate($src, $angle, 0);
                    // recalculamos dimensiones tras la rotación
                    $w = imagesx($src); $h = imagesy($src);
                }
            }
        }

        // Crop cuadrado centrado
        $side = min($w, $h);
        $srcX = (int) (($w - $side) / 2);
        $srcY = (int) (($h - $side) / 2);

        $dst = imagecreatetruecolor($size, $size);
        // Para PNG con transparencia: fondo blanco (el carnet la usa con
        // border-radius circular, no nos sirve fondo transparente).
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $size, $size, $white);
        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $size, $size, $side, $side);

        ob_start();
        imagejpeg($dst, null, $quality);
        $binary = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return $binary;
    }

    /** POST /area-personal/foto/eliminar */
    public function eliminarFoto(Request $request)
    {
        [$user] = $this->ensureAuth();
        if ($user->profile_image) {
            try {
                \Illuminate\Support\Facades\Storage::disk('public')
                    ->delete($user->profile_image);
            } catch (\Throwable $e) { /* silent */ }
            $user->update(['profile_image' => null]);
        }
        return back()->with('status', 'Foto eliminada.');
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
