<?php

namespace Tests\Feature;

use App\Livewire\CheckoutForm;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Cart;
use App\Services\RedsysPaymentService;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pago con Redsys (TPV Virtual) y cierre del agujero de /pago-app/{ref}/simulado.
 *
 * Este test crea y borra datos: ejecútalo SIEMPRE con BD en memoria, porque
 * phpunit.xml apunta a la SQLite local de desarrollo:
 *
 *   DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test --filter=RedsysPagoTest
 *
 * Sin esas variables se salta (no usa RefreshDatabase, que vaciaría la BD local
 * antes de poder comprobarlo).
 */
class RedsysPagoTest extends TestCase
{
    private const DS_ORDER = '000112345678';

    protected function setUp(): void
    {
        parent::setUp();

        $conexion = config('database.default');
        if (config("database.connections.{$conexion}.database") !== ':memory:') {
            $this->markTestSkipped('Ejecutar con DB_CONNECTION=sqlite DB_DATABASE=:memory: (ver docblock).');
        }

        $this->artisan('migrate');
        Storage::fake('public');

        config([
            'services.payment.gateway' => 'redsys',
            'services.stripe.secret'   => '',
            'redsys.env'               => 'test',
            'redsys.merchant_code'     => '370436875',
            'redsys.terminal'          => '100',
            'redsys.sha256_key'        => 'sq7HjrUOBfKmC576ILgskD5srU870gJ7',
        ]);
    }

    public function test_simulado_rechaza_un_pedido_que_hay_que_pagar(): void
    {
        $order = $this->pedidoPendiente(78.75);

        $this->post(route('pago-app.simulado', $order->reference))->assertForbidden();

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(0, Ticket::count());
    }

    public function test_simulado_confirma_un_pedido_gratis(): void
    {
        $order = $this->pedidoPendiente(0.0);

        $this->post(route('pago-app.simulado', $order->reference))
            ->assertRedirect(route('pago-app.exito', $order->reference));

        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_pago_app_ofrece_redsys_aunque_no_haya_claves_de_stripe(): void
    {
        $order = $this->pedidoPendiente(78.75);

        $this->actingAs(User::factory()->create())
            ->get(route('pago-app', $order->reference))
            ->assertOk()
            ->assertSee(route('redsys.start', $order->reference), false)
            ->assertDontSee('Confirmar reserva (sin cobro)');
    }

    public function test_notify_con_firma_valida_marca_pagado_y_emite_el_abono_una_sola_vez(): void
    {
        $order = $this->pedidoPendiente(78.75);
        $notificacion = $this->notificacion(7875, '0000');

        $this->post(route('redsys.notify'), $notificacion)->assertOk();
        $this->post(route('redsys.notify'), $notificacion)->assertOk(); // Redsys reintenta

        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertSame('redsys:' . self::DS_ORDER, $order->payment_intent_id);
        $this->assertSame(1, Ticket::count());
    }

    public function test_notify_con_firma_manipulada_no_cambia_nada(): void
    {
        $order = $this->pedidoPendiente(78.75);
        $notificacion = $this->notificacion(7875, '0000');
        $notificacion['Ds_Signature'] = 'AAAA' . substr($notificacion['Ds_Signature'], 4);

        $this->post(route('redsys.notify'), $notificacion)->assertOk();

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(0, Ticket::count());
    }

    public function test_notify_con_importe_distinto_no_marca_pagado(): void
    {
        $order = $this->pedidoPendiente(78.75);

        $this->post(route('redsys.notify'), $this->notificacion(100, '0000'))->assertOk();

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_retorno_ok_muestra_el_pedido_aunque_el_notify_llegara_antes(): void
    {
        $order = $this->pedidoPendiente(78.75);
        $notificacion = $this->notificacion(7875, '0000');
        $this->post(route('redsys.notify'), $notificacion)->assertOk();

        $this->get(route('redsys.ok', ['o' => self::DS_ORDER] + $notificacion))
            ->assertOk()
            ->assertSee($order->reference);

        $this->assertSame(1, Ticket::count());
    }

    public function test_retorno_ok_con_parametros_firmados_confirma_si_el_notify_no_llega(): void
    {
        $order = $this->pedidoPendiente(78.75);

        $this->get(route('redsys.ok', ['o' => self::DS_ORDER] + $this->notificacion(7875, '0000')))
            ->assertOk();

        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_checkout_del_carrito_exige_condiciones_y_manda_a_redsys(): void
    {
        $producto = $this->producto('merch', 12.0);
        app(Cart::class)->add($producto->id, null, 1);

        $this->formularioCarrito()
            ->call('submit')
            ->assertHasErrors(['accept_terms' => 'accepted']);
        $this->assertSame(0, Order::count());

        $this->formularioCarrito()
            ->set('accept_terms', true)
            ->call('submit')
            ->assertRedirect(route('redsys.start', Order::first()->reference));

        $this->assertSame('pending', Order::first()->status);
        $this->assertTrue(app(Cart::class)->items()->isEmpty());
    }

    // === Helpers ===

    private function formularioCarrito()
    {
        return Livewire::test(CheckoutForm::class)
            ->set('first_name', 'Ana')
            ->set('last_name', 'Prueba')
            ->set('email', 'ana.prueba@example.com')
            ->set('address', 'Calle Real 123')
            ->set('city', 'Algeciras')
            ->set('postal_code', '11201');
    }

    private function producto(string $type, float $price): Product
    {
        return Product::create([
            'sku'      => strtoupper($type) . '-' . uniqid(),
            'type'     => $type,
            'name'     => ['es' => 'Producto de prueba ' . $type],
            'price'    => $price,
            'vat_rate' => 21,
            'active'   => true,
        ]);
    }

    private function pedidoPendiente(float $total): Order
    {
        $producto = $this->producto('abono', 75.0);

        $order = Order::create([
            'reference'         => Order::nextReference(),
            'guest_email'       => 'test@example.com',
            'status'            => 'pending',
            'channel'           => 'web',
            'subtotal'          => 61.98,
            'vat'               => 13.02,
            'shipping_cost'     => 0,
            'gestion_fee'       => 3.75,
            'total'             => $total,
            'currency'          => 'EUR',
            'payment_gateway'   => 'redsys',
            'payment_intent_id' => self::DS_ORDER,
        ]);

        OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $producto->id,
            'product_type' => 'abono',
            'name'         => 'Abono de prueba',
            'sku'          => $producto->sku,
            'qty'          => 1,
            'unit_price'   => 75.0,
            'vat_rate'     => 21,
            'subtotal'     => 61.98,
            'vat_amount'   => 13.02,
            'total'        => 75.0,
        ]);

        return $order;
    }

    /** Notificación como la manda Redsys, firmada con la clave de pruebas. */
    private function notificacion(int $importeCentimos, string $respuesta): array
    {
        $params = base64_encode(json_encode([
            'Ds_Amount'            => (string) $importeCentimos,
            'Ds_Currency'          => '978',
            'Ds_Order'             => self::DS_ORDER,
            'Ds_MerchantCode'      => '370436875',
            'Ds_Terminal'          => '100',
            'Ds_Response'          => $respuesta,
            'Ds_TransactionType'   => '0',
            'Ds_AuthorisationCode' => '410088',
        ]));

        $firmador = new class extends RedsysPaymentService {
            public function firmar(string $params, string $order): string
            {
                return $this->createSignature($params, $order);
            }
        };

        return [
            'Ds_SignatureVersion'   => 'HMAC_SHA256_V1',
            'Ds_MerchantParameters' => $params,
            'Ds_Signature'          => $firmador->firmar($params, self::DS_ORDER),
        ];
    }
}
