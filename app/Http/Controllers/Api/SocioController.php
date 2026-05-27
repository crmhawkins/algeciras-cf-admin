<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CustomerCoupon;
use App\Models\NotificationPreference;
use Illuminate\Http\Request;

/**
 * Endpoints específicos del área socio (cupones, preferencias, actividad).
 * Todos requieren auth:sanctum y un Customer asociado al User.
 */
class SocioController extends Controller
{
    /** GET /api/socio/cupones — cupones del socio (disponibles + canjeados) */
    public function cupones(Request $request)
    {
        $customer = $this->customer($request);
        if (! $customer) return response()->json(['disponibles' => [], 'canjeados' => []]);

        $rows = $customer->customerCoupons()->with('coupon')->get();

        return response()->json([
            'disponibles' => $rows->where('status', 'available')->values()->map(fn ($cc) => $this->serializeCoupon($cc)),
            'canjeados'   => $rows->where('status', 'redeemed')->values()->map(fn ($cc) => $this->serializeCoupon($cc)),
        ]);
    }

    /** POST /api/socio/cupones/{id}/canjear — marcar un cupón como usado */
    public function canjearCupon(Request $request, int $couponId)
    {
        $customer = $this->customer($request);
        $cc = $customer?->customerCoupons()->where('coupon_id', $couponId)->where('status', 'available')->first();

        if (! $cc) {
            return response()->json(['error' => 'Cupón no disponible.'], 404);
        }

        $cc->redeem('app');
        return response()->json(['ok' => true, 'cupon' => $this->serializeCoupon($cc->fresh()->load('coupon'))]);
    }

    /** GET /api/socio/notificaciones — preferencias actuales */
    public function notificaciones(Request $request)
    {
        $customer = $this->customer($request);
        if (! $customer) return response()->json(['categorias' => NotificationPreference::categories(), 'prefs' => []]);

        $prefs = $customer->notificationPreferences()->get()->keyBy('category');
        $result = [];
        foreach (NotificationPreference::categories() as $cat => $label) {
            $row = $prefs->get($cat);
            $result[$cat] = [
                'label'   => $label,
                'email'   => $row?->email_enabled ?? true,
                'push'    => $row?->push_enabled  ?? true,
            ];
        }

        return response()->json(['prefs' => $result]);
    }

    /** PUT /api/socio/notificaciones — actualiza preferencias */
    public function actualizarNotificaciones(Request $request)
    {
        $customer = $this->customer($request);
        if (! $customer) return response()->json(['error' => 'Sin perfil de socio.'], 400);

        $data = $request->validate([
            'prefs'           => 'required|array',
            'prefs.*.email'   => 'boolean',
            'prefs.*.push'    => 'boolean',
        ]);

        foreach ($data['prefs'] as $category => $opts) {
            if (! array_key_exists($category, NotificationPreference::categories())) continue;
            NotificationPreference::updateOrCreate(
                ['customer_id' => $customer->id, 'category' => $category],
                ['email_enabled' => (bool) ($opts['email'] ?? true), 'push_enabled' => (bool) ($opts['push'] ?? true)],
            );
        }

        return response()->json(['ok' => true]);
    }

    /** GET /api/socio/actividad — votos MVP + asistencias del socio */
    public function actividad(Request $request)
    {
        $customer = $this->customer($request);
        if (! $customer) return response()->json(['votosMvp' => [], 'asistencias' => []]);

        $votos = $customer->mvpVotes()->with('player', 'match')->latest()->limit(50)->get()->map(fn ($v) => [
            'id' => $v->id,
            'fecha' => $v->created_at?->toIso8601String(),
            'partido' => ['id' => $v->match?->id, 'rival' => $v->match?->opponent ?? null],
            'jugador' => ['id' => $v->player?->id, 'nombre' => $v->player?->display_name ?? $v->player?->name],
        ]);

        $asistencias = $customer->matchAttendances()->with('match')->latest()->limit(50)->get()->map(fn ($a) => [
            'id' => $a->id,
            'fecha' => $a->checked_in_at?->toIso8601String(),
            'partido' => ['id' => $a->match?->id, 'rival' => $a->match?->opponent ?? null],
            'gate' => $a->gate,
        ]);

        return response()->json([
            'votosMvp'        => $votos,
            'asistencias'     => $asistencias,
            'attendanceRate'  => $customer->attendance_rate,
        ]);
    }

    private function customer(Request $request): ?\App\Models\Customer
    {
        return $request->user()?->customer;
    }

    private function serializeCoupon(CustomerCoupon $cc): array
    {
        $c = $cc->coupon;
        return [
            'id'            => $c->id,
            'code'          => $c->code,
            'title'         => $c->title,
            'description'   => $c->description,
            'type'          => $c->type,
            'value'         => $c->value,
            'displayValue'  => $c->display_value,
            'image'         => $c->image,
            'validFrom'     => $c->valid_from?->toDateString(),
            'validUntil'    => $c->valid_until?->toDateString(),
            'status'        => $cc->status,
            'redeemedAt'    => $cc->redeemed_at?->toIso8601String(),
        ];
    }
}
