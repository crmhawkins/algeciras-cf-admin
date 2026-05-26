<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Autenticación unificada para web y app móvil (Algeciras CF).
 *
 * Endpoints expuestos en español (matching la app React Native existente):
 *   POST /api/authenticate                       → login
 *   POST /api/authenticate/recuperar-password    → enviar email reset
 *   POST /api/user/create                        → registro
 *   PUT  /api/user/profile                       → editar perfil
 *   PUT  /api/user/change-password               → cambiar password
 *   PUT  /api/user/profile-image                 → subir foto perfil
 *   PUT  /api/user/push-token                    → registrar token notif push
 *   POST /api/logout                             → invalidar token
 *   GET  /api/me                                 → datos del usuario actual
 */
class AuthController extends Controller
{
    /** POST /api/authenticate — login email+password → devuelve token Bearer */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
            'device'   => 'nullable|string', // ej: 'iPhone de Iván'
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'error'   => true,
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $user->update(['last_login_at' => now()]);

        // Si el user no tiene customer asociado, crea uno básico
        $customer = $user->customer;
        if (! $customer) {
            $customer = Customer::create([
                'user_id'    => $user->id,
                'email'      => $user->email,
                'first_name' => $user->name,
                'last_name'  => '',
            ]);
        }

        $token = $user->createToken($data['device'] ?? 'app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'usuario' => $this->serializeUser($user->fresh()->load('customer')),
        ]);
    }

    /** POST /api/user/create — registro */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:120',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:6',
            'first_name' => 'nullable|string|max:80',
            'last_name'  => 'nullable|string|max:80',
            'phone'      => 'nullable|string|max:32',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Customer::create([
            'user_id'    => $user->id,
            'email'      => $data['email'],
            'first_name' => $data['first_name'] ?? $data['name'],
            'last_name'  => $data['last_name']  ?? '',
            'phone'      => $data['phone']      ?? null,
        ]);

        $token = $user->createToken('app-registro')->plainTextToken;

        return response()->json([
            'token'   => $token,
            'usuario' => $this->serializeUser($user->fresh()->load('customer')),
        ], 201);
    }

    /** POST /api/authenticate/recuperar-password — envía email de reset */
    public function recuperarPassword(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        // Laravel built-in password reset broker
        $status = Password::sendResetLink(['email' => $data['email']]);

        // Por seguridad siempre devolvemos 200 (no revelamos si el email existe)
        return response()->json([
            'ok'      => $status === Password::RESET_LINK_SENT,
            'message' => 'Si el email existe en nuestros registros, recibirás un enlace de recuperación en unos minutos.',
        ]);
    }

    /** PUT /api/user/profile — editar perfil (auth) */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'        => 'nullable|string|max:120',
            'email'       => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'first_name'  => 'nullable|string|max:80',
            'last_name'   => 'nullable|string|max:80',
            'phone'       => 'nullable|string|max:32',
            'dni'         => 'nullable|string|max:24',
            'birth_date'  => 'nullable|date',
            'address'     => 'nullable|string|max:200',
            'city'        => 'nullable|string|max:80',
            'province'    => 'nullable|string|max:80',
            'postal_code' => 'nullable|string|max:10',
        ]);

        if (! empty($data['name']))  $user->name  = $data['name'];
        if (! empty($data['email'])) $user->email = $data['email'];
        $user->save();

        $customer = $user->customer ?? Customer::create(['user_id' => $user->id, 'email' => $user->email]);
        $customer->fill(array_filter([
            'first_name'  => $data['first_name']  ?? null,
            'last_name'   => $data['last_name']   ?? null,
            'phone'       => $data['phone']       ?? null,
            'dni'         => $data['dni']         ?? null,
            'birth_date'  => $data['birth_date']  ?? null,
            'address'     => $data['address']     ?? null,
            'city'        => $data['city']        ?? null,
            'province'    => $data['province']    ?? null,
            'postal_code' => $data['postal_code'] ?? null,
        ], fn ($v) => $v !== null))->save();

        return response()->json([
            'usuario' => $this->serializeUser($user->fresh()->load('customer')),
        ]);
    }

    /** PUT /api/user/change-password */
    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'error'   => true,
                'message' => 'La contraseña actual no es correcta.',
            ], 422);
        }

        $user->update(['password' => Hash::make($data['new_password'])]);

        return response()->json(['ok' => true, 'message' => 'Contraseña actualizada.']);
    }

    /** PUT /api/user/profile-image — subir foto perfil (multipart) */
    public function updateProfileImage(Request $request)
    {
        $data = $request->validate([
            'image' => 'required|image|max:5120', // 5MB max
        ]);

        $user = $request->user();
        $path = $data['image']->store('avatars', 'public');
        $user->update(['profile_image' => $path]);

        return response()->json([
            'ok'    => true,
            'url'   => Storage::disk('public')->url($path),
            'usuario' => $this->serializeUser($user->fresh()->load('customer')),
        ]);
    }

    /** PUT /api/user/push-token — registrar token de notificación push de Expo */
    public function updatePushToken(Request $request)
    {
        $data = $request->validate([
            'push_token' => 'required|string',
        ]);

        $request->user()->update(['push_token' => $data['push_token']]);

        return response()->json(['ok' => true]);
    }

    /** POST /api/logout — invalida el token actual */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['ok' => true]);
    }

    /** GET /api/me — datos del usuario autenticado */
    public function me(Request $request)
    {
        return response()->json([
            'usuario' => $this->serializeUser($request->user()->load('customer')),
        ]);
    }

    /**
     * Serializa el User+Customer en una sola estructura para la app y la web.
     */
    private function serializeUser(User $user): array
    {
        $customer = $user->customer;
        return [
            'id'           => $user->id,
            'name'         => $user->name,
            'email'        => $user->email,
            'profileImage' => $user->profile_image ? Storage::disk('public')->url($user->profile_image) : null,
            'isSocio'      => (bool) ($customer?->is_socio ?? false),
            'socioNumber'  => $customer?->socio_number,
            'lastLoginAt'  => $user->last_login_at?->toIso8601String(),
            'customer'     => $customer ? [
                'id'         => $customer->id,
                'firstName'  => $customer->first_name,
                'lastName'   => $customer->last_name,
                'phone'      => $customer->phone,
                'dni'        => $customer->dni,
                'birthDate'  => $customer->birth_date?->toDateString(),
                'address'    => $customer->address,
                'city'       => $customer->city,
                'province'   => $customer->province,
                'postalCode' => $customer->postal_code,
                'country'    => $customer->country,
                'isSocio'    => (bool) $customer->is_socio,
                'socioNumber'=> $customer->socio_number,
                'socioSince' => $customer->socio_since?->toDateString(),
                'language'   => $customer->language,
            ] : null,
        ];
    }
}
