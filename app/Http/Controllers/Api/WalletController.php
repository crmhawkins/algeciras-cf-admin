<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\AppleWalletService;
use App\Services\GoogleWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Endpoints "Añadir a Wallet" para el dueño del abono.
 *
 * GET /api/me/abonos/{ticket}/apple-wallet   → descarga .pkpass
 * GET /api/me/abonos/{ticket}/google-wallet  → JSON con `saveUrl`
 *
 * Ambos requieren auth:sanctum y comprueban que el ticket pertenece al
 * customer del usuario logueado.
 */
class WalletController extends Controller
{
    public function __construct(
        private AppleWalletService $apple,
        private GoogleWalletService $google,
    ) {}

    public function appleWallet(Request $request, Ticket $ticket): BinaryFileResponse|JsonResponse
    {
        $authErr = $this->ensureOwnership($request, $ticket);
        if ($authErr) {
            return $authErr;
        }

        try {
            $path = $this->apple->generateForTicket($ticket);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => 'Funcionalidad próximamente disponible',
                'detail'  => $e->getMessage(),
            ], 500);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Error generando pass de Apple Wallet',
                'detail'  => $e->getMessage(),
            ], 500);
        }

        return response()->download(
            $path,
            'algeciras-cf-abono.pkpass',
            ['Content-Type' => 'application/vnd.apple.pkpass']
        );
    }

    public function googleWallet(Request $request, Ticket $ticket): JsonResponse
    {
        $authErr = $this->ensureOwnership($request, $ticket);
        if ($authErr) {
            return $authErr;
        }

        try {
            $saveUrl = $this->google->generateSaveUrl($ticket);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => 'Funcionalidad próximamente disponible',
                'detail'  => $e->getMessage(),
            ], 500);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Error generando link de Google Wallet',
                'detail'  => $e->getMessage(),
            ], 500);
        }

        return response()->json(['saveUrl' => $saveUrl]);
    }

    private function ensureOwnership(Request $request, Ticket $ticket): ?JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }
        $myCustomerId = $user->customer?->id;
        if (! $myCustomerId || (int) $ticket->customer_id !== (int) $myCustomerId) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        $ticket->loadMissing('product');
        if (($ticket->product?->type ?? null) !== 'abono') {
            return response()->json(['message' => 'Este ticket no es un abono'], 422);
        }
        return null;
    }
}
