<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerCoupon;
use App\Models\FootballMatch;
use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Season;
use App\Models\Sector;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder demo del área socio: crea un usuario de prueba completo con
 *  - User + Customer (is_socio)
 *  - 1 Order de abono + 1 Ticket de abono
 *  - 2 Tickets de entrada (vinculados a los próximos partidos si existen)
 *  - 1 Order de tienda con 2 items merch
 *  - 3 cupones disponibles asociados
 *  - 7 preferencias de notificación
 *
 * Idempotente: re-ejecutar no duplica datos (usa updateOrCreate / firstOrCreate).
 */
class SocioDemoSeeder extends Seeder
{
    public function run(): void
    {
        // ============ 1. USER ============
        $user = User::updateOrCreate(
            ['email' => 'socio@algeciras.test'],
            [
                'name'              => 'Iván Socio Demo',
                'password'          => Hash::make('algeciras2026'),
                'email_verified_at' => now(),
            ]
        );

        // ============ 2. CUSTOMER ============
        $customer = Customer::updateOrCreate(
            ['email' => 'socio@algeciras.test'],
            [
                'user_id'          => $user->id,
                'first_name'       => 'Iván',
                'last_name'        => 'García Socio',
                'phone'            => '+34 600 123 456',
                'dni'              => '12345678A',
                'birth_date'       => '1985-06-15',
                'address'          => 'Avda. Virgen del Carmen 35',
                'city'             => 'Algeciras',
                'province'         => 'Cádiz',
                'postal_code'      => '11201',
                'country'          => 'ES',
                'is_socio'         => true,
                'socio_number'     => '00042',
                'socio_since'      => '2012-08-15',
                'language'         => 'es',
                'newsletter_optin' => true,
                'whatsapp_optin'   => true,
            ]
        );

        // ============ 3. SECTOR + PRODUCTS ============
        $sector = Sector::where('zone', 'tribuna_baja')->where('available', true)->first();
        $season = Season::where('current', true)->first();

        // Producto abono: busca uno existente o crea uno mínimo on-the-fly.
        $abonoProduct = Product::where('type', Product::TYPE_ABONO)->first()
            ?? Product::firstOrCreate(
                ['sku' => 'ABO-DEMO-26'],
                [
                    'type'          => Product::TYPE_ABONO,
                    'name'          => ['es' => 'Abono Demo 2026-27', 'en' => 'Demo Season Pass 2026-27'],
                    'description'   => ['es' => 'Abono demo para pruebas.', 'en' => 'Demo season pass.'],
                    'price'         => 120.00,
                    'vat_rate'      => 10,
                    'season_id'     => $season?->id,
                    'ship_required' => false,
                    'active'        => true,
                ]
            );

        // Producto entrada: si no existe ningún Product type=entrada, crea uno mínimo.
        $entradaProduct = Product::where('type', Product::TYPE_ENTRADA)->first()
            ?? Product::firstOrCreate(
                ['sku' => 'ENT-DEMO-GEN'],
                [
                    'type'          => Product::TYPE_ENTRADA,
                    'name'          => ['es' => 'Entrada General Demo', 'en' => 'Demo General Ticket'],
                    'description'   => ['es' => 'Entrada demo para pruebas.', 'en' => 'Demo single-match ticket.'],
                    'price'         => 25.00,
                    'vat_rate'      => 10,
                    'season_id'     => $season?->id,
                    'ship_required' => false,
                    'active'        => true,
                ]
            );

        // ============ 4. ORDER DE ABONO + TICKET ABONO ============
        $abonoPrice = (float) $abonoProduct->price;
        $abonoSubtotal = round($abonoPrice / (1 + ($abonoProduct->vat_rate / 100)), 2);
        $abonoVat = round($abonoPrice - $abonoSubtotal, 2);

        $orderAbono = Order::updateOrCreate(
            ['reference' => 'DEMO-ABONO-001'],
            [
                'customer_id'      => $customer->id,
                'status'           => 'paid',
                'channel'          => 'web',
                'subtotal'         => $abonoSubtotal,
                'vat'              => $abonoVat,
                'shipping_cost'    => 0,
                'total'            => $abonoPrice,
                'currency'         => 'EUR',
                'payment_gateway'  => 'stripe',
                'paid_at'          => now()->subDays(20),
            ]
        );

        $orderItemAbono = OrderItem::firstOrCreate(
            ['order_id' => $orderAbono->id, 'product_id' => $abonoProduct->id],
            [
                'product_type' => Product::TYPE_ABONO,
                'name'         => $abonoProduct->getTranslation('name', 'es'),
                'sku'          => $abonoProduct->sku,
                'qty'          => 1,
                'unit_price'   => $abonoPrice,
                'vat_rate'     => $abonoProduct->vat_rate,
                'subtotal'     => $abonoSubtotal,
                'vat_amount'   => $abonoVat,
                'total'        => $abonoPrice,
                'meta'         => ['demo' => true, 'sector' => $sector?->name],
            ]
        );

        Ticket::firstOrCreate(
            [
                'order_item_id' => $orderItemAbono->id,
                'product_id'    => $abonoProduct->id,
                'customer_id'   => $customer->id,
            ],
            [
                'season_id'    => $season?->id,
                'zone_id'      => $abonoProduct->zone_id,
                'status'       => 'issued',
                'holder_name'  => $customer->full_name,
                'holder_dni'   => $customer->dni,
                'valid_from'   => now(),
                'valid_until'  => now()->addYear(),
            ]
        );

        // ============ 5. TICKETS DE ENTRADA (2) ============
        $upcomingMatches = FootballMatch::where('venue', 'home')
            ->where('status', 'scheduled')
            ->orderBy('kickoff_at')
            ->take(2)
            ->get();

        $entradaPrice    = (float) $entradaProduct->price;
        $entradaSubtotal = round($entradaPrice / (1 + ($entradaProduct->vat_rate / 100)), 2);
        $entradaVat      = round($entradaPrice - $entradaSubtotal, 2);

        // Order para las entradas (idempotente por reference)
        $orderEntradas = Order::updateOrCreate(
            ['reference' => 'DEMO-ENTRADAS-001'],
            [
                'customer_id'     => $customer->id,
                'status'          => 'paid',
                'channel'         => 'web',
                'subtotal'        => $entradaSubtotal * 2,
                'vat'             => $entradaVat * 2,
                'shipping_cost'   => 0,
                'total'           => $entradaPrice * 2,
                'currency'        => 'EUR',
                'payment_gateway' => 'stripe',
                'paid_at'         => now()->subDays(5),
            ]
        );

        // Idempotente: solo creamos los items de entradas si la orden está vacía.
        if ($orderEntradas->items()->count() < 2) {
            // Limpia cualquier item previo huérfano para evitar duplicados parciales.
            $orderEntradas->items()->delete();
            $orderEntradas->tickets()->delete();

            for ($i = 0; $i < 2; $i++) {
                $match = $upcomingMatches->get($i); // puede ser null si no hay partidos

                $orderItemEntrada = OrderItem::create([
                    'order_id'     => $orderEntradas->id,
                    'product_id'   => $entradaProduct->id,
                    'product_type' => Product::TYPE_ENTRADA,
                    'name'         => $entradaProduct->getTranslation('name', 'es'),
                    'sku'          => $entradaProduct->sku,
                    'qty'          => 1,
                    'unit_price'   => $entradaPrice,
                    'vat_rate'     => $entradaProduct->vat_rate,
                    'subtotal'     => $entradaSubtotal,
                    'vat_amount'   => $entradaVat,
                    'total'        => $entradaPrice,
                    'meta'         => [
                        'demo'  => true,
                        'slot'  => 'entrada-'.($i + 1),
                        'match' => $match?->opponent,
                    ],
                ]);

                Ticket::create([
                    'order_item_id' => $orderItemEntrada->id,
                    'product_id'    => $entradaProduct->id,
                    'customer_id'   => $customer->id,
                    'match_id'      => $match?->id,
                    'season_id'     => $season?->id,
                    'zone_id'       => $entradaProduct->zone_id,
                    'status'        => 'issued',
                    'holder_name'   => $customer->full_name,
                    'holder_dni'    => $customer->dni,
                    'valid_from'    => $match?->kickoff_at?->copy()->subHours(2) ?? now(),
                    'valid_until'   => $match?->kickoff_at?->copy()->addHours(4) ?? now()->addDays(30),
                ]);
            }
        }

        // ============ 6. ORDER DE TIENDA CON 2 MERCH ============
        $merch = Product::where('type', Product::TYPE_MERCH)->take(2)->get();
        if ($merch->count() < 2) {
            // Fallback: crea camiseta + bufanda mínimos para que /compras tenga datos.
            $camiseta = Product::firstOrCreate(
                ['sku' => 'DEMO-CAM'],
                [
                    'type' => Product::TYPE_MERCH,
                    'name' => ['es' => 'Camiseta Demo', 'en' => 'Demo Jersey'],
                    'description' => ['es' => 'Demo merch.', 'en' => 'Demo merch.'],
                    'price' => 65.00,
                    'vat_rate' => 21,
                    'ship_required' => true,
                    'active' => true,
                ]
            );
            $bufanda = Product::firstOrCreate(
                ['sku' => 'DEMO-BUF'],
                [
                    'type' => Product::TYPE_MERCH,
                    'name' => ['es' => 'Bufanda Demo', 'en' => 'Demo Scarf'],
                    'description' => ['es' => 'Demo merch.', 'en' => 'Demo merch.'],
                    'price' => 12.00,
                    'vat_rate' => 21,
                    'ship_required' => true,
                    'active' => true,
                ]
            );
            $merch = collect([$camiseta, $bufanda]);
        }

        $merchSubtotal = 0;
        $merchVat = 0;
        $merchTotal = 0;
        $merchItems = [];
        foreach ($merch as $m) {
            $price = (float) $m->price;
            $sub   = round($price / (1 + ($m->vat_rate / 100)), 2);
            $vat   = round($price - $sub, 2);
            $merchSubtotal += $sub;
            $merchVat      += $vat;
            $merchTotal    += $price;
            $merchItems[]  = compact('m', 'price', 'sub', 'vat');
        }

        $orderTienda = Order::updateOrCreate(
            ['reference' => 'DEMO-TIENDA-001'],
            [
                'customer_id'      => $customer->id,
                'status'           => 'fulfilled',
                'channel'          => 'web',
                'subtotal'         => round($merchSubtotal, 2),
                'vat'              => round($merchVat, 2),
                'shipping_cost'    => 0,
                'total'            => round($merchTotal, 2),
                'currency'         => 'EUR',
                'payment_gateway'  => 'stripe',
                'paid_at'          => now()->subDays(15),
                'fulfilled_at'     => now()->subDays(10),
                'shipping_address' => [
                    'name'        => $customer->full_name,
                    'address'     => $customer->address,
                    'city'        => $customer->city,
                    'province'    => $customer->province,
                    'postal_code' => $customer->postal_code,
                    'country'     => $customer->country,
                ],
            ]
        );

        foreach ($merchItems as $row) {
            $p = $row['m'];
            OrderItem::firstOrCreate(
                ['order_id' => $orderTienda->id, 'product_id' => $p->id],
                [
                    'product_type' => Product::TYPE_MERCH,
                    'name'         => $p->getTranslation('name', 'es'),
                    'sku'          => $p->sku,
                    'qty'          => 1,
                    'unit_price'   => $row['price'],
                    'vat_rate'     => $p->vat_rate,
                    'subtotal'     => $row['sub'],
                    'vat_amount'   => $row['vat'],
                    'total'        => $row['price'],
                    'meta'         => ['demo' => true],
                ]
            );
        }

        // ============ 7. CUPONES (3 disponibles) ============
        $couponCodes = ['ALGECF15', 'ABONADO30', 'BUFANDA-FREE'];
        foreach ($couponCodes as $code) {
            $coupon = Coupon::where('code', $code)->first();
            if (! $coupon) continue;
            CustomerCoupon::firstOrCreate(
                ['customer_id' => $customer->id, 'coupon_id' => $coupon->id],
                ['status' => 'available']
            );
        }

        // ============ 8. PREFERENCIAS DE NOTIFICACIÓN ============
        foreach (NotificationPreference::categories() as $category => $label) {
            NotificationPreference::updateOrCreate(
                ['customer_id' => $customer->id, 'category' => $category],
                ['email_enabled' => true, 'push_enabled' => true]
            );
        }

        $this->command?->info(
            "Demo socio creado: socio@algeciras.test / algeciras2026 (socio nº {$customer->socio_number})"
        );
    }
}
