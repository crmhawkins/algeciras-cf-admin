<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Customer;

/**
 * Controller del Área Personal pública (web).
 *
 * Usa el guard `web` (sesión cookie de Laravel) — el mismo User+Customer
 * que la API usa con Sanctum tokens. Resultado: la cuenta del socio
 * funciona en web Y app móvil con las mismas credenciales.
 */
class AreaPersonalController extends Controller
{
    /** GET /area-personal — login si no auth, dashboard si auth */
    public function index(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user()->load('customer');
            $customer = $user->customer;

            // Obtener tickets si el customer existe
            $abonos = collect();
            $entradas = collect();
            if ($customer) {
                $tickets = $customer->tickets()->with('product', 'zone', 'order')->get();
                $abonos = $tickets->filter(fn ($t) => optional($t->product)->type === 'abono');
                $entradas = $tickets->filter(fn ($t) => optional($t->product)->type === 'entrada');
            }

            return view('pages.area-personal-dashboard', compact('user', 'customer', 'abonos', 'entradas'));
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
}
