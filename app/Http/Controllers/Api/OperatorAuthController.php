<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Login específico para la PWA del operador de puerta (`validator-pwa`).
 *
 * Diferencias con `AuthController::login` (app móvil del socio):
 *   - Solo aceptan usuarios con role 'operator' o 'admin'.
 *   - El token Sanctum se emite con ability `scope:operator`, que el
 *     `ValidatorController` exige via `tokenCan()` antes de validar QRs
 *     o exponer stats live.
 *   - Devuelve un payload mínimo (solo name+email) — no se filtra el
 *     resto del perfil porque la PWA no lo necesita y esto reduce
 *     superficie de información sensible en el dispositivo del operador.
 *
 * Ruta: POST /api/operator/login  (sin auth previo)
 */
class OperatorAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciales inválidas o usuario no autorizado',
            ], 401);
        }

        // Solo operadores de puerta y admins pueden acceder a la PWA.
        if (! in_array($user->role, ['operator', 'admin'], true)) {
            return response()->json([
                'message' => 'Credenciales inválidas o usuario no autorizado',
            ], 401);
        }

        $user->update(['last_login_at' => now()]);

        // Token Sanctum con ability scope:operator — los endpoints del
        // validador comprueban este scope antes de operar.
        $token = $user->createToken('validator-pwa', ['scope:operator'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
